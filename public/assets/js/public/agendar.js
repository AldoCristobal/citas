// public/assets/js/public/agendar.js
(() => {
   const $ = (id) => document.getElementById(id);

   // selects
   const sedeSel = $("sede_id");
   const tramiteSel = $("tramite_id");

   // month nav
   const btnPrev = $("btnPrev");
   const btnNext = $("btnNext");
   const mesLabel = $("mesLabel");

   // calendar + hint
   const cal = $("cal");
   const calLoader = $("calLoader");
   const dayHint = $("dayHint");

   // horario + acciones
   const horaSel = $("hora_sel") || $("slotSelect"); // soporte legacy
   const btnAgendar = $("btnAgendar");

   // hold ui
   const holdBar = $("holdBar");
   const holdCountdown = $("holdCountdown");
   const holdMini = $("holdMini");

   // form (card abajo)
   const formCardWrap = $("formCardWrap");
   const formLoader = $("formLoader");
   const btnConfirmar = $("btnConfirmar");
   const holdTokenEl = $("hold_token");

   // campos (identidad)
   const fNombre = $("f_nombre");
   const fApellidoPaterno = $("f_apellido_paterno");
   const fApellidoMaterno = $("f_apellido_materno");
   const fCurp = $("f_curp_rfc");
   const fFechaNacimiento = $("f_fecha_nacimiento");
   const fEdad = $("f_edad");

   // contacto
   const fTelefono = $("f_telefono");
   const fEmail = $("f_email");

   // dirección
   const fCalle = $("f_calle");
   const fNumeroExterior = $("f_numero_exterior");
   const fNumeroInterior = $("f_numero_interior");
   const fColonia = $("f_colonia");
   const fCodigoPostal = $("f_codigo_postal");
   const fEstado = $("f_estado");
   const fMunicipio = $("f_municipio");

   const errBox = $("err");

   let mesCursor = ymToday(); // "YYYY-MM"
   let selectedDate = null; // "YYYY-MM-DD"
   let selectedHora = null; // "HH:MM"
   let monthData = null;

   // hold timer
   let holdExpiresAtMs = 0;
   let holdTimer = null;

   // =============== helpers ===============
   const CAL_LOADER_MS = 3000;
   const FORM_LOADER_MS = 3000;

   const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

   function scrollToEl(el) {
      if (!el) return;
      try {
         el.scrollIntoView({ behavior: "smooth", block: "start" });
      } catch (_) {
         // fallback
         const top = el.getBoundingClientRect().top + window.scrollY - 12;
         window.scrollTo(0, top);
      }
   }

   function showCalendarLoading(on) {
      if (calLoader) calLoader.classList.toggle("d-none", !on);
      if (cal) cal.classList.toggle("d-none", on);
   }

   function showFormLoading(on) {
      if (formLoader) formLoader.classList.toggle("d-none", !on);
   }

   function setMonthNavEnabled(on) {
      if (btnPrev) btnPrev.disabled = !on;
      if (btnNext) btnNext.disabled = !on;
   }

   // ================= CURP helpers =================
   const STRICT_CURP_DV = false;

   const CURP_REGEX =
      /^[A-Z][AEIOUX][A-Z]{2}\d{2}(0[1-9]|1[0-2])(0[1-9]|[12]\d|3[01])[HM](AS|BC|BS|CC|CL|CM|CS|CH|DF|DG|GT|GR|HG|JC|MC|MN|MS|NT|NL|OC|PL|QT|QR|SP|SL|SR|TC|TS|TL|VZ|YN|ZS|NE)[B-DF-HJ-NP-TV-Z]{3}[A-Z0-9]\d$/;

   const DV_DICT = "0123456789ABCDEFGHIJKLMNÑOPQRSTUVWXYZ";
   const DV_MAP = (() => {
      const m = new Map();
      for (let i = 0; i < DV_DICT.length; i++) m.set(DV_DICT[i], i);
      return m;
   })();

   function curpNormalize(v) {
      return String(v || "").trim().toUpperCase();
   }
   function curpFormatoOk(curp) {
      const c = curpNormalize(curp);
      return CURP_REGEX.test(c);
   }
   function curpDvEsperado(curp) {
      const c = curpNormalize(curp);
      if (c.length !== 18) return null;
      let sum = 0;
      for (let i = 0; i < 17; i++) {
         const ch = c[i];
         const val = DV_MAP.has(ch) ? DV_MAP.get(ch) : 0;
         const weight = 18 - (i + 1);
         sum += val * weight;
      }
      const dv = (10 - (sum % 10)) % 10;
      return String(dv);
   }
   function curpDvOk(curp) {
      const c = curpNormalize(curp);
      if (c.length !== 18) return false;
      const exp = curpDvEsperado(c);
      return exp !== null && c[17] === exp;
   }

   function setInputState(el, state /* 'valid'|'invalid'|'neutral' */) {
      if (!el) return;
      el.classList.remove("is-valid", "is-invalid");
      if (state === "valid") el.classList.add("is-valid");
      if (state === "invalid") el.classList.add("is-invalid");
   }

   function forceUppercaseInput(el) {
      if (!el) return;
      const start = el.selectionStart;
      const end = el.selectionEnd;
      const up = el.value.toUpperCase();
      if (el.value !== up) {
         el.value = up;
         try {
            el.setSelectionRange(start, end);
         } catch (_) { }
      }
   }

   function calcAge(birthDate) {
      const now = new Date();
      let age = now.getFullYear() - birthDate.getFullYear();
      const m = now.getMonth() - birthDate.getMonth();
      if (m < 0 || (m === 0 && now.getDate() < birthDate.getDate())) age--;
      return age;
   }

   function parseCurpBirthDate(curp) {
      const c = curpNormalize(curp);
      if (c.length < 10) return null;

      const yy = parseInt(c.slice(4, 6), 10);
      const mm = parseInt(c.slice(6, 8), 10);
      const dd = parseInt(c.slice(8, 10), 10);
      if (!yy || !mm || !dd) return null;

      const candidates = [1900 + yy, 2000 + yy]
         .map((year) => {
            const dt = new Date(year, mm - 1, dd);
            if (dt.getFullYear() !== year || dt.getMonth() !== mm - 1 || dt.getDate() !== dd) return null;
            const age = calcAge(dt);
            return { dt, age };
         })
         .filter(Boolean);

      const ok = candidates.filter((x) => x.age >= 0 && x.age <= 125);
      if (ok.length === 1) return ok[0];
      if (ok.length > 1) {
         ok.sort((a, b) => b.age - a.age);
         return ok[0];
      }
      return null;
   }

   function fmtDateYYYYMMDD(dt) {
      const y = dt.getFullYear();
      const m = String(dt.getMonth() + 1).padStart(2, "0");
      const d = String(dt.getDate()).padStart(2, "0");
      return `${y}-${m}-${d}`;
   }

   function setBirthFromCurpIfAllowed() {
      if (!fCurp || !fFechaNacimiento || !fEdad) return;

      const curp = curpNormalize(fCurp.value);
      if (!curpFormatoOk(curp)) return;

      const parsed = parseCurpBirthDate(curp);
      if (!parsed) return;

      const birthStr = fmtDateYYYYMMDD(parsed.dt);
      const ageStr = String(parsed.age);

      const fechaWasAuto = fFechaNacimiento.dataset.auto === "1";
      const edadWasAuto = fEdad.dataset.auto === "1";

      if (!fFechaNacimiento.value || fechaWasAuto) {
         fFechaNacimiento.value = birthStr;
         fFechaNacimiento.dataset.auto = "1";
      }
      if (!fEdad.value || edadWasAuto) {
         fEdad.value = ageStr;
         fEdad.dataset.auto = "1";
      }
   }

   function validateCurpInput() {
      if (!fCurp) return;

      forceUppercaseInput(fCurp);
      const curp = curpNormalize(fCurp.value);

      if (!curp) {
         setInputState(fCurp, "neutral");
         return;
      }
      if (!curpFormatoOk(curp)) {
         setInputState(fCurp, "invalid");
         return;
      }

      if (!STRICT_CURP_DV) {
         setInputState(fCurp, "valid");
         setBirthFromCurpIfAllowed();
         return;
      }

      const ok = curpDvOk(curp);
      setInputState(fCurp, ok ? "valid" : "invalid");
      if (ok) setBirthFromCurpIfAllowed();
   }

   // ---------------- UI helpers ----------------
   function showErr(msg) {
      if (errBox) {
         errBox.textContent = msg || "";
         errBox.classList.toggle("d-none", !msg);
      } else if (msg) {
         alert(msg);
      }
   }

   // ✅ ya no usamos el loader viejo
   function setLoading(_on) { }

   function showForm(on) {
      if (!formCardWrap) return;
      formCardWrap.classList.toggle("d-none", !on);
   }

   function showHoldUI(on) {
      if (holdBar) holdBar.classList.toggle("d-none", !on);
      if (holdMini) holdMini.classList.toggle("d-none", !on);
   }

   function setCountdownText(mmss) {
      if (holdCountdown) holdCountdown.textContent = mmss;
   }

   function stopHoldTimer() {
      if (holdTimer) {
         clearInterval(holdTimer);
         holdTimer = null;
      }
      holdExpiresAtMs = 0;
   }

   function clearInputs() {
      const fields = [
         fNombre,
         fApellidoPaterno,
         fApellidoMaterno,
         fCurp,
         fFechaNacimiento,
         fEdad,
         fTelefono,
         fEmail,
         fCalle,
         fNumeroExterior,
         fNumeroInterior,
         fColonia,
         fCodigoPostal,
         fEstado,
         fMunicipio,
      ];
      fields.forEach((el) => {
         if (!el) return;
         el.value = "";
         delete el.dataset.auto;
      });
      setInputState(fCurp, "neutral");
   }

   function clearAllState(messageForHint = "", opts = { resetSelection: false }) {
      stopHoldTimer();

      if (holdTokenEl) holdTokenEl.value = "";
      showHoldUI(false);
      setCountdownText("10:00");

      showForm(false);
      showFormLoading(false);

      if (btnConfirmar) btnConfirmar.disabled = true;

      clearInputs();

      if (opts?.resetSelection) {
         selectedHora = null;
         selectedDate = null;
      }

      if (messageForHint && dayHint) dayHint.textContent = messageForHint;
      refreshActions();
   }

   function resetSlots(placeholder = "Selecciona una fecha") {
      if (!horaSel) return;
      horaSel.innerHTML = `<option value="">${placeholder}</option>`;
      horaSel.disabled = true;
      selectedHora = null;
      refreshActions();
   }

   function resetCalendar(msg) {
      if (cal) cal.innerHTML = "";
      monthData = null;
      selectedDate = null;

      if (dayHint) dayHint.textContent = msg || "Selecciona un día";
      resetSlots("Selecciona una fecha");
      clearAllState("", { resetSelection: true });
   }

   function refreshActions() {
      const sedeId = Number(sedeSel?.value || 0);
      const tramiteId = Number(tramiteSel?.value || 0);
      const canHold = !!sedeId && !!tramiteId && !!selectedDate && !!selectedHora;
      if (btnAgendar) btnAgendar.disabled = !canHold;

      const hasHold = !!(holdTokenEl?.value || "").trim();
      const nombre = (fNombre?.value || "").trim();
      const email = (fEmail?.value || "").trim();
      const curp = curpNormalize(fCurp?.value || "");

      const nombreOk = nombre.length >= 3;
      const emailOk = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
      const curpOk = curpFormatoOk(curp);

      if (btnConfirmar) btnConfirmar.disabled = !(hasHold && nombreOk && curpOk && emailOk);
   }

   function startHoldCountdown(expiresAtIso) {
      stopHoldTimer();

      const ms = Date.parse(expiresAtIso || "");
      if (!ms || Number.isNaN(ms)) {
         showHoldUI(true);
         setCountdownText("10:00");
         return;
      }

      holdExpiresAtMs = ms;
      showHoldUI(true);

      const tick = async () => {
         const left = holdExpiresAtMs - Date.now();

         if (left <= 0) {
            setCountdownText("00:00");
            stopHoldTimer();

            showErr("⏳ El tiempo de reserva expiró. Vuelve a seleccionar el horario y presiona Agendar.");
            clearAllState("", { resetSelection: false });

            if (selectedDate) await loadDaySlots(selectedDate).catch(() => { });
            return;
         }

         const totalSec = Math.floor(left / 1000);
         const mm = String(Math.floor(totalSec / 60)).padStart(2, "0");
         const ss = String(totalSec % 60).padStart(2, "0");
         setCountdownText(`${mm}:${ss}`);
      };

      tick();
      holdTimer = setInterval(tick, 1000);
   }

   // ---------------- http ----------------
   async function apiGet(url) {
      const res = await fetch(url, { credentials: "same-origin" });
      const j = await res.json().catch(() => null);
      return j ?? { ok: false, error: { code: "CLIENT", message: "Respuesta inválida" } };
   }

   async function apiPost(url, body) {
      const res = await fetch(url, {
         method: "POST",
         credentials: "same-origin",
         headers: { "Content-Type": "application/json" },
         body: JSON.stringify(body || {}),
      });
      const j = await res.json().catch(() => null);
      return j ?? { ok: false, error: { code: "CLIENT", message: "Respuesta inválida" } };
   }

   // ---------------- date helpers ----------------
   function ymToday() {
      const d = new Date();
      const y = d.getFullYear();
      const m = String(d.getMonth() + 1).padStart(2, "0");
      return `${y}-${m}`;
   }

   function addMonths(ym, delta) {
      const [y, m] = ym.split("-").map(Number);
      const dt = new Date(y, m - 1 + delta, 1);
      const yy = dt.getFullYear();
      const mm = String(dt.getMonth() + 1).padStart(2, "0");
      return `${yy}-${mm}`;
   }

   function fmtMes(ym) {
      const [y, m] = ym.split("-").map(Number);
      const dt = new Date(y, m - 1, 1);
      return dt.toLocaleDateString("es-MX", { month: "long", year: "numeric" });
   }

   function daysInMonth(ym) {
      const [y, m] = ym.split("-").map(Number);
      return new Date(y, m, 0).getDate();
   }

   function dowOfFirst(ym) {
      const [y, m] = ym.split("-").map(Number);
      const d = new Date(y, m - 1, 1);
      const js = d.getDay();
      return js === 0 ? 7 : js;
   }

   // ---------------- catalogs ----------------
   async function loadSedes() {
      showErr("");
      try {
         const s = await apiGet("/api/v1/public/sedes");
         if (!s.ok) throw new Error(s?.error?.message || "No se pudieron cargar sedes");

         sedeSel.innerHTML = `<option value="">Selecciona una sede</option>`;
         (s.data || []).forEach((x) => {
            const o = document.createElement("option");
            o.value = x.id;
            o.textContent = x.nombre;
            sedeSel.appendChild(o);
         });
      } catch (e) {
         showErr(e?.message || "Error al cargar sedes");
      }
   }

   async function loadTramitesBySede(sedeId) {
      showErr("");
      if (!tramiteSel) return;

      tramiteSel.disabled = true;
      tramiteSel.innerHTML = `<option value="">Cargando trámites…</option>`;

      const qs = new URLSearchParams({ sede_id: String(sedeId) });
      let t = await apiGet("/api/v1/public/tramites?" + qs.toString());

      if (!t.ok && (t?.error?.code === "NOT_FOUND" || /Ruta no encontrada/i.test(t?.error?.message || ""))) {
         t = await apiGet("/api/v1/public/tramites");
      }

      if (!t.ok) throw new Error(t?.error?.message || "No se pudieron cargar trámites");

      tramiteSel.innerHTML = `<option value="">Selecciona un trámite</option>`;
      (t.data || []).forEach((x) => {
         const o = document.createElement("option");
         o.value = x.id;
         o.textContent = x.nombre;
         tramiteSel.appendChild(o);
      });

      tramiteSel.disabled = false;
   }

   // ---------------- month/day ----------------
   async function loadMonth() {
      showErr("");

      const sedeId = Number(sedeSel?.value || 0);
      const tramiteId = Number(tramiteSel?.value || 0);

      if (!sedeId) {
         resetCalendar("Selecciona una sede");
         return;
      }
      if (!tramiteId) {
         setMonthNavEnabled(false);
         resetCalendar("Selecciona un trámite");
         return;
      }
      setMonthNavEnabled(true);


      if (mesLabel) mesLabel.textContent = fmtMes(mesCursor);

      const qs = new URLSearchParams({ sede_id: sedeId, tramite_id: tramiteId, mes: mesCursor });
      const j = await apiGet("/api/v1/public/availability/month?" + qs.toString());
      if (!j.ok) throw new Error(j?.error?.message || "No se pudo cargar disponibilidad mensual");

      monthData = j.data;
      selectedDate = null;

      if (dayHint) dayHint.textContent = "Selecciona un día";

      resetSlots("Selecciona una fecha");
      clearAllState("", { resetSelection: false });

      renderCalendar();
   }

   async function showCalendarLoaderAndLoadMonth() {
      // ✅ loader 3s “en el calendario”
      showCalendarLoading(true);
      try {
         await Promise.all([sleep(CAL_LOADER_MS), loadMonth()]);
      } catch (e) {
         showErr(e?.message || "Error al cargar calendario");
         if (cal) cal.innerHTML = "";
      } finally {
         showCalendarLoading(false);
      }
   }

   function renderCalendar() {
      if (!cal) return;

      const ym = mesCursor;
      const totalDays = daysInMonth(ym);
      const firstDow = dowOfFirst(ym);

      const agendables = new Set(monthData?.dias_agendables || []);
      const enVentana = new Set(monthData?.dias_en_ventana || []);

      cal.innerHTML = "";

      const head = document.createElement("div");
      head.className = "cal__head";
      ["Lun", "Mar", "Mié", "Jue", "Vie", "Sáb", "Dom"].forEach((d) => {
         const c = document.createElement("div");
         c.textContent = d;
         head.appendChild(c);
      });
      cal.appendChild(head);

      const grid = document.createElement("div");
      grid.className = "cal__grid";

      for (let i = 1; i < firstDow; i++) {
         const cell = document.createElement("div");
         cell.className = "cal__cell cal__cell--off";
         grid.appendChild(cell);
      }

      for (let day = 1; day <= totalDays; day++) {
         const dd = String(day).padStart(2, "0");
         const fecha = `${ym}-${dd}`;

         const cell = document.createElement("div");
         cell.className = "cal__cell";

         const isInWindow = enVentana.has(fecha);
         const isAvailable = agendables.has(fecha);

         if (!isInWindow) cell.classList.add("cal__cell--off");
         else if (isAvailable) cell.classList.add("cal__cell--available");
         else cell.classList.add("cal__cell--unavailable");

         if (selectedDate === fecha) cell.classList.add("cal__cell--selected");

         cell.innerHTML = `<div class="cal__daynum">${day}</div>`;

         cell.addEventListener("click", async () => {
            if (!isInWindow) return;

            selectedDate = fecha;
            renderCalendar();

            resetSlots("Cargando horarios…");
            clearAllState("", { resetSelection: false });

            await loadDaySlots(fecha);
         });

         grid.appendChild(cell);
      }

      while (grid.children.length < 42) {
         const cell = document.createElement("div");
         cell.className = "cal__cell cal__cell--off";
         grid.appendChild(cell);
      }

      cal.appendChild(grid);
   }

   async function loadDaySlots(fecha) {
      showErr("");
      const sedeId = Number(sedeSel?.value || 0);
      const tramiteId = Number(tramiteSel?.value || 0);
      if (!sedeId || !tramiteId) return;
      if (!horaSel) return;

      try {
         const qs = new URLSearchParams({ sede_id: sedeId, tramite_id: tramiteId, fecha });
         const j = await apiGet("/api/v1/public/availability/day?" + qs.toString());
         if (!j.ok) throw new Error(j?.error?.message || "No se pudo cargar horarios del día");

         const horas = j.data?.horas_disponibles || [];

         horaSel.innerHTML = `<option value="">Selecciona un horario</option>`;
         horaSel.disabled = !horas.length;
         selectedHora = null;

         refreshActions();

         if (!horas.length) {
            if (dayHint) dayHint.textContent = `Sin horarios disponibles para ${fecha}`;
            return;
         }

         if (dayHint) dayHint.textContent = `Horarios disponibles para ${fecha}`;

         horas.forEach((h) => {
            const opt = document.createElement("option");
            opt.value = h;
            opt.textContent = h;
            horaSel.appendChild(opt);
         });
      } catch (e) {
         showErr(e?.message || "Error al cargar horarios");
      }
   }

   // ---------------- HOLD + CONFIRM ----------------
   async function crearHold() {
      showErr("");

      const sedeId = Number(sedeSel?.value || 0);
      const tramiteId = Number(tramiteSel?.value || 0);
      const fecha = selectedDate;
      const hora = selectedHora;

      if (!sedeId || !tramiteId || !fecha || !hora) {
         showErr("Completa sede, trámite, fecha y horario.");
         return;
      }

      // deshabilita mientras hace hold
      if (btnAgendar) btnAgendar.disabled = true;

      try {
         const j = await apiPost("/api/v1/public/holds", { sede_id: sedeId, tramite_id: tramiteId, fecha, hora });
         if (!j.ok) throw new Error(j?.error?.message || "No se pudo crear el hold");

         const token = j.data?.hold_token || "";
         const expiresAt = j.data?.expires_at || "";
         if (!token) throw new Error("Hold creado pero no se recibió hold_token");

         if (holdTokenEl) holdTokenEl.value = token;

         // ✅ Mostrar card + loader del formulario 3s + scroll
         showForm(true);
         showFormLoading(true);
         scrollToEl(formCardWrap);

         await sleep(FORM_LOADER_MS);

         showFormLoading(false);
         startHoldCountdown(expiresAt);

         refreshActions();
      } catch (e) {
         showErr(e?.message || "Error al generar hold");
      } finally {
         refreshActions();
      }
   }

   function formatFechaMX(yyyy_mm_dd) {
      if (!yyyy_mm_dd) return "";
      const [y, m, d] = String(yyyy_mm_dd).split("-");
      if (!y || !m || !d) return String(yyyy_mm_dd);
      return `${d}/${m}/${y}`;
   }

   function buildDireccion() {
      const calle = (fCalle?.value || "").trim();
      const ne = (fNumeroExterior?.value || "").trim();
      const ni = (fNumeroInterior?.value || "").trim();
      const col = (fColonia?.value || "").trim();
      const cp = (fCodigoPostal?.value || "").trim();
      const edo = (fEstado?.value || "").trim();
      const mun = (fMunicipio?.value || "").trim();

      const partes = [];
      const linea1 = [calle, ne ? `#${ne}` : "", ni ? `Int. ${ni}` : ""].filter(Boolean).join(" ");
      if (linea1) partes.push(linea1);
      const linea2 = [col, cp ? `CP ${cp}` : ""].filter(Boolean).join(", ");
      if (linea2) partes.push(linea2);
      const linea3 = [mun, edo].filter(Boolean).join(", ");
      if (linea3) partes.push(linea3);

      return partes.join(" · ") || "—";
   }

   function showComprobante(data) {
      const wrap = document.getElementById("comprobanteWrap");
      if (!wrap) return;

      const folio = data?.folio || data?.folio_publico || "—";
      const sede = data?.sede?.nombre || "—";
      const fecha = formatFechaMX(data?.fecha || "");
      const hora = data?.hora || "—";
      const tramite = data?.tramite?.nombre || "—";

      const nombre = (fNombre?.value || "").trim() || "—";
      const apP = (fApellidoPaterno?.value || "").trim();
      const apM = (fApellidoMaterno?.value || "").trim();
      const curp = (fCurp?.value || "").trim() || "—";
      const tel = (fTelefono?.value || "").trim() || "—";
      const email = (fEmail?.value || "").trim() || "—";
      const nac = (fFechaNacimiento?.value || "").trim();
      const edad = (fEdad?.value || "").trim();
      const nacEdad = [nac ? formatFechaMX(nac) : "", edad ? `${edad} años` : ""].filter(Boolean).join(" · ") || "—";

      const dir = buildDireccion();

      const set = (id, val) => {
         const el = document.getElementById(id);
         if (el) el.textContent = val;
      };

      set("cmpFolio", folio);
      set("cmpSede", sede);
      set("cmpFecha", fecha);
      set("cmpHora", hora);
      set("cmpTramite", tramite);

      set("cmpNombre", nombre);
      set("cmpApellidos", [apP, apM].filter(Boolean).join(" ") || "—");
      set("cmpCurp", curp);
      set("cmpTel", tel);
      set("cmpEmail", email);
      set("cmpNacEdad", nacEdad);
      set("cmpDir", dir);

      const tokEl = document.getElementById("cmpAccessToken");
      if (tokEl) tokEl.value = data?.access_token || "";

      wrap.classList.remove("d-none");

      // bind una sola vez
      const btnPrint = document.getElementById("btnImprimirCmp");
      const btnClose = document.getElementById("btnCerrarCmp");

      if (btnPrint && !btnPrint.dataset.bound) {
         btnPrint.dataset.bound = "1";
         btnPrint.addEventListener("click", () => printComprobante());
      }

      if (btnClose && !btnClose.dataset.bound) {
         btnClose.dataset.bound = "1";
         btnClose.addEventListener("click", () => wrap.classList.add("d-none"));
      }
   }

   function printComprobante() {
      const wrap = document.getElementById("comprobanteWrap");
      if (!wrap) return;

      const html = `
<html>
<head>
<meta charset="utf-8" />
<title>Comprobante de cita</title>
<style>
  body{font-family: Arial, sans-serif; padding:20px;}
  .card{border:1px solid #ddd; border-radius:10px; padding:16px;}
  .muted{color:#666; font-size:12px;}
  .row{display:flex; flex-wrap:wrap; gap:12px;}
  .col{flex:1 1 220px;}
  .hr{margin:14px 0; border-top:1px solid #eee;}
  .bold{font-weight:700;}
</style>
</head>
<body>
  ${wrap.querySelector(".card")?.outerHTML || ""}
  <script>window.onload=()=>{window.print();}</script>
</body>
</html>`;

      const w = window.open("", "_blank", "noopener,noreferrer");
      if (!w) return;
      w.document.open();
      w.document.write(html);
      w.document.close();
   }

   async function confirmarCita() {
      showErr("");

      const token = (holdTokenEl?.value || "").trim();
      if (!token) {
         showErr("Primero debes presionar Agendar para generar el hold.");
         return;
      }

      const payload = {
         hold_token: token,

         nombre: (fNombre?.value || "").trim(),
         curp_rfc: curpNormalize(fCurp?.value || ""),
         telefono: (fTelefono?.value || "").trim(),
         email: (fEmail?.value || "").trim(),

         apellido_paterno: (fApellidoPaterno?.value || "").trim(),
         apellido_materno: (fApellidoMaterno?.value || "").trim(),

         calle: (fCalle?.value || "").trim(),
         numero_exterior: (fNumeroExterior?.value || "").trim(),
         numero_interior: (fNumeroInterior?.value || "").trim(),
         colonia: (fColonia?.value || "").trim(),
         codigo_postal: (fCodigoPostal?.value || "").trim(),
         estado: (fEstado?.value || "").trim(),
         municipio: (fMunicipio?.value || "").trim(),

         fecha_nacimiento: (fFechaNacimiento?.value || "").trim(),
         edad: (fEdad?.value || "").trim(),
      };

      if (!payload.nombre || payload.nombre.length < 3) {
         showErr("Nombre requerido (mín. 3).");
         return;
      }
      if (!curpFormatoOk(payload.curp_rfc)) {
         showErr("CURP inválida.");
         return;
      }
      if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(payload.email)) {
         showErr("Email inválido.");
         return;
      }

      if (btnConfirmar) btnConfirmar.disabled = true;

      try {
         const j = await apiPost("/api/v1/public/citas/confirmar", payload);
         if (!j.ok) throw new Error(j?.error?.message || "No se pudo confirmar la cita");

         // mostrar comprobante (se pinta antes de limpiar)
         showComprobante(j.data);

         // ✅ SweetAlert2 con acciones
         const folio = j.data?.folio || j.data?.folio_publico || "";
         const hasSwal = typeof window.Swal?.fire === "function";

         if (hasSwal) {
            const r = await window.Swal.fire({
               icon: "success",
               title: "Cita confirmada",
               html: folio ? `<div class="mt-1">Folio: <b>${folio}</b></div>` : "",
               showDenyButton: true,
               showCancelButton: true,
               confirmButtonText: "Ver comprobante",
               denyButtonText: "Imprimir",
               cancelButtonText: "Cerrar",
            });

            if (r.isConfirmed) {
               const wrap = document.getElementById("comprobanteWrap");
               wrap?.classList.remove("d-none");
               scrollToEl(wrap);
            } else if (r.isDenied) {
               printComprobante();
            }
         } else {
            alert(folio ? `✅ Cita confirmada. Folio: ${folio}` : "✅ Cita confirmada.");
            const wrap = document.getElementById("comprobanteWrap");
            wrap?.classList.remove("d-none");
            scrollToEl(wrap);
         }

         // ✅ Limpia el hold + inputs (mantiene selección para re-agendar si quieres)
         clearAllState("", { resetSelection: false });

         if (selectedDate) await loadDaySlots(selectedDate);
      } catch (e) {
         showErr(e?.message || "No se pudo confirmar la cita");
      } finally {
         refreshActions();
      }
   }

   // ---------------- init ----------------
   async function init() {
      if (mesLabel) mesLabel.textContent = fmtMes(mesCursor);

      if (tramiteSel) {
         tramiteSel.disabled = true;
         tramiteSel.innerHTML = `<option value="">Selecciona sede primero</option>`;
      }

      showForm(false);
      showHoldUI(false);
      setCountdownText("10:00");

      resetCalendar("Selecciona una sede");
      showCalendarLoading(false);
      setMonthNavEnabled(false);

      await loadSedes();

      sedeSel?.addEventListener("change", async () => {
         showErr("");
         clearAllState("", { resetSelection: true });
         monthData = null;
         resetSlots("Selecciona una fecha");
         setMonthNavEnabled(false);

         const sedeId = Number(sedeSel.value || 0);
         if (!sedeId) {
            if (tramiteSel) {
               tramiteSel.disabled = true;
               tramiteSel.innerHTML = `<option value="">Selecciona sede primero</option>`;
            }
            if (cal) cal.innerHTML = "";
            if (dayHint) dayHint.textContent = "Selecciona una sede";
            refreshActions();
            return;
         }

         try {
            await loadTramitesBySede(sedeId);
            if (dayHint) dayHint.textContent = "Selecciona un trámite";
            if (cal) cal.innerHTML = "";
         } catch (e) {
            showErr(e?.message || "Error al cargar trámites");
         }

         refreshActions();
      });

      // ✅ AL CAMBIAR TRÁMITE: loader 3s en el calendario y luego carga mes
      tramiteSel?.addEventListener("change", async () => {
         const tramiteId = Number(tramiteSel?.value || 0);
         setMonthNavEnabled(!!tramiteId);

         clearAllState("", { resetSelection: true });
         monthData = null;
         resetSlots("Selecciona una fecha");
         await showCalendarLoaderAndLoadMonth();
      });

      btnPrev?.addEventListener("click", async () => {
         mesCursor = addMonths(mesCursor, -1);
         await showCalendarLoaderAndLoadMonth();
      });

      btnNext?.addEventListener("click", async () => {
         mesCursor = addMonths(mesCursor, 1);
         await showCalendarLoaderAndLoadMonth();
      });

      horaSel?.addEventListener("change", () => {
         selectedHora = (horaSel.value || "").trim() || null;
         clearAllState("", { resetSelection: false });
         refreshActions();
      });

      btnAgendar?.addEventListener("click", crearHold);

      const hook = () => refreshActions();

      [
         fNombre,
         fApellidoPaterno,
         fApellidoMaterno,
         fEmail,
         fTelefono,
         fCalle,
         fNumeroExterior,
         fNumeroInterior,
         fColonia,
         fCodigoPostal,
         fEstado,
         fMunicipio,
         fFechaNacimiento,
         fEdad,
      ].forEach((el) => el?.addEventListener("input", hook));

      fCurp?.addEventListener("input", () => {
         validateCurpInput();
         refreshActions();
      });
      fCurp?.addEventListener("blur", () => {
         validateCurpInput();
         refreshActions();
      });

      fFechaNacimiento?.addEventListener("input", () => {
         fFechaNacimiento.dataset.auto = "0";
         try {
            if (fFechaNacimiento.value) {
               const dt = new Date(fFechaNacimiento.value + "T00:00:00");
               const age = calcAge(dt);
               if (!Number.isNaN(age) && age >= 0 && age <= 125) {
                  fEdad.value = String(age);
                  fEdad.dataset.auto = "0";
               }
            }
         } catch (_) { }
         refreshActions();
      });

      fEdad?.addEventListener("input", () => {
         fEdad.dataset.auto = "0";
         refreshActions();
      });

      btnConfirmar?.addEventListener("click", confirmarCita);

      validateCurpInput();
      refreshActions();
   }

   function boot() {
      init().catch((err) => {
         console.error(err);
         showErr(err?.message || "Error");
      });
   }

   if (document.readyState === "loading") {
      document.addEventListener("DOMContentLoaded", boot);
   } else {
      boot();
   }
})();
