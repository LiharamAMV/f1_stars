/**
 * F1 Stars - Main Application Logic
 * Pure modern vanilla JS with API and local fallback
 */

const F1App = (function() {
    let cachedData = null;

    // Mobile nav toggle
    function initNav() {
        const toggle = document.querySelector('.nav-toggle');
        const nav = document.querySelector('.site-nav');
        if (toggle && nav) {
            toggle.addEventListener('click', () => {
                nav.classList.toggle('open');
            });
        }

        // Highlight current nav item
        const path = window.location.pathname.replace(/\/index\.html$/, '/');
        document.querySelectorAll('.site-nav a').forEach(link => {
            const href = link.getAttribute('href');
            if (href === path || (href !== '/' && path.startsWith(href))) {
                link.classList.add('active');
            } else if (path === '/' && href === '/') {
                link.classList.add('active');
            } else {
                link.classList.remove('active');
            }
        });
    }

    // Generic API fetch with fallback to /data/data.json
    async function fetchData() {
        if (cachedData) return cachedData;
        try {
            // First try api endpoint
            const res = await fetch('/api/sessions');
            if (res.ok) {
                const json = await res.json();
                if (json && json.success) {
                    cachedData = json.data;
                    return cachedData;
                }
            }
        } catch (e) {
            // API not reachable, try static json fallback
        }

        try {
            const fallbackRes = await fetch('/data/data.json');
            if (fallbackRes.ok) {
                cachedData = await fallbackRes.json();
                return cachedData;
            }
        } catch (err) {
            console.error('Failed to load F1 Stars data:', err);
        }
        return null;
    }

    // -------------------------------------------------------------------------
    // HOME PAGE: Standings & Chart
    // -------------------------------------------------------------------------
    async function initStandings() {
        const tbody = document.getElementById('f1-standings-body');
        if (!tbody) return;

        let standings = [];
        try {
            const res = await fetch('/api/standings');
            if (res.ok) {
                const json = await res.json();
                if (json.success && json.data) standings = json.data;
            }
        } catch (e) {}

        if (!standings || standings.length === 0) {
            const data = await fetchData();
            if (data && data.participants && data.bets) {
                const map = {};
                data.participants.forEach(p => {
                    map[p.nick] = { nick: p.nick, points: 0, bets: 0 };
                });
                data.bets.forEach(b => {
                    if (map[b.participant_nick]) {
                        map[b.participant_nick].points += (b.points || 0);
                        map[b.participant_nick].bets += 1;
                    }
                });
                standings = Object.values(map).sort((a, b) => b.points - a.points);
            }
        }

        const emojis = ['👑', '👏', '🤡', '💩'];
        tbody.innerHTML = '';
        standings.forEach((item, index) => {
            const tr = document.createElement('tr');
            const medal = emojis[index] || (index + 1);
            tr.innerHTML = `
                <td class="position">${medal}</td>
                <td class="nick"><strong>${item.nick}</strong></td>
                <td class="points"><strong>${item.points}</strong></td>
                <td class="bets">${item.bets}</td>
            `;
            tbody.appendChild(tr);
        });

        // Load Results Button
        const loadBtn = document.getElementById('f1-load-results-btn');
        const loadMsg = document.getElementById('f1-load-results-msg');
        if (loadBtn) {
            loadBtn.addEventListener('click', async () => {
                loadBtn.disabled = true;
                loadBtn.innerHTML = '⏳ Sincronizando con OpenF1...';
                if (loadMsg) {
                    loadMsg.style.display = 'block';
                    loadMsg.className = 'f1-alert info';
                    loadMsg.textContent = 'Consultando resultados oficiales en OpenF1...';
                }

                try {
                    const res = await fetch('/api/sync-results', { method: 'POST' });
                    const json = await res.json();
                    if (json && json.success) {
                        if (loadMsg) {
                            loadMsg.className = 'f1-alert success';
                            loadMsg.textContent = json.message || 'Resultados actualizados correctamente.';
                        }
                        // Refresh standings and chart
                        setTimeout(() => {
                            cachedData = null;
                            initStandings();
                            initChart();
                        }, 1200);
                    } else {
                        if (loadMsg) {
                            loadMsg.className = 'f1-alert error';
                            loadMsg.textContent = (json && json.message) || 'No hay nuevas sesiones completadas por ahora.';
                        }
                    }
                } catch (e) {
                    if (loadMsg) {
                        loadMsg.className = 'f1-alert info';
                        loadMsg.textContent = 'Resultados al día. (Temporada 2026 actualizada)';
                    }
                } finally {
                    loadBtn.disabled = false;
                    loadBtn.innerHTML = '📊 Cargar Resultados';
                }
            });
        }
    }

    async function initChart() {
        const canvas = document.getElementById('f1PointsChart');
        const status = document.getElementById('f1-chart-status');
        const wrap = document.getElementById('f1-chart-canvas-wrap');
        if (!canvas) return;

        let chartData = null;
        try {
            const res = await fetch('/api/chart-data');
            if (res.ok) {
                const json = await res.json();
                if (json.success && json.data) chartData = json.data;
            }
        } catch (e) {}

        if (!chartData || !chartData.series || chartData.series.length === 0) {
            const data = await fetchData();
            if (data && data.bets && data.sessions) {
                // Generate cumulative series from local data
                const completedSessions = data.sessions.filter(s => s.is_completed);
                const labels = [];
                const fullNames = [];
                const gpsSeen = new Set();
                const sessionGPs = [];

                completedSessions.forEach(s => {
                    const gpName = s.country;
                    if (!gpsSeen.has(gpName)) {
                        gpsSeen.add(gpName);
                        labels.push(gpName);
                        fullNames.push(s.round_name || `GP ${gpName}`);
                    }
                    sessionGPs.push({ session_id: s.id, gp: gpName });
                });

                const byNick = {};
                data.participants.forEach(p => { byNick[p.nick] = {}; });

                data.bets.forEach(b => {
                    if (b.points !== null && b.points !== undefined) {
                        const sess = sessionGPs.find(sg => sg.session_id === b.session_id);
                        if (sess && byNick[b.participant_nick]) {
                            byNick[b.participant_nick][sess.gp] = (byNick[b.participant_nick][sess.gp] || 0) + b.points;
                        }
                    }
                });

                const series = [];
                Object.keys(byNick).forEach(nick => {
                    let total = 0;
                    const cumulative = labels.map(gp => {
                        total += (byNick[nick][gp] || 0);
                        return total;
                    });
                    series.push({ nick: nick, data: cumulative });
                });

                chartData = { labels: labels, full_names: fullNames, series: series };
            }
        }

        if (!chartData || !chartData.series || chartData.series.length === 0) {
            if (status) status.textContent = '🏁 Aún no hay resultados puntuados.';
            return;
        }

        if (status) status.style.display = 'none';
        if (wrap) wrap.style.display = 'block';

        const colors = ["#e10600", "#00d2be", "#0600ef", "#ff8700", "#006f62", "#a855f7"];
        const datasets = chartData.series.map((s, i) => {
            const col = colors[i % colors.length];
            return {
                label: s.nick,
                data: s.data,
                borderColor: col,
                backgroundColor: col + '18',
                borderWidth: 2.5,
                pointRadius: 4,
                pointHoverRadius: 7,
                pointBackgroundColor: col,
                tension: 0.25,
                fill: false
            };
        });

        if (window.f1ChartInstance) {
            window.f1ChartInstance.destroy();
        }

        window.f1ChartInstance = new Chart(canvas, {
            type: 'line',
            data: {
                labels: chartData.labels,
                datasets: datasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: '#e2e8f0',
                            padding: 14,
                            font: { size: 13, weight: '600' },
                            usePointStyle: true
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(15,15,25,0.95)',
                        titleColor: '#fff',
                        bodyColor: '#cbd5e1',
                        borderColor: 'rgba(255,255,255,0.1)',
                        borderWidth: 1,
                        padding: 12,
                        callbacks: {
                            title: (items) => (chartData.full_names && chartData.full_names[items[0].dataIndex]) || items[0].label,
                            label: (item) => `  ${item.dataset.label}: ${item.parsed.y} pts`
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { color: 'rgba(255,255,255,0.06)' },
                        ticks: { color: '#94a3b8', font: { size: 11 } }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(255,255,255,0.06)' },
                        ticks: { color: '#94a3b8', font: { size: 12 }, precision: 0 },
                        title: { display: true, text: 'Puntos acumulados', color: '#64748b', font: { size: 11 } }
                    }
                }
            }
        });
    }

    // -------------------------------------------------------------------------
    // APOSTAR PAGE: Betting Form
    // -------------------------------------------------------------------------
    async function initBetForm() {
        const form = document.getElementById('f1-bet-form');
        if (!form) return;

        const data = await fetchData();
        if (!data) return;

        // Populate sessions select (pending ones)
        const sessionSelect = document.getElementById('session');
        if (sessionSelect) {
            sessionSelect.innerHTML = '<option value="">-- Selecciona una sesión --</option>';
            const pending = data.sessions.filter(s => !s.is_completed);
            pending.forEach(s => {
                const opt = document.createElement('option');
                opt.value = s.id;
                // Format session date nicely
                const d = new Date(s.session_date);
                const day = String(d.getDate()).padStart(2, '0');
                const month = String(d.getMonth() + 1).padStart(2, '0');
                const year = d.getFullYear();
                const hours = String(d.getHours()).padStart(2, '0');
                const mins = String(d.getMinutes()).padStart(2, '0');
                const dateStr = `${day}/${month}/${year} ${hours}:${mins}`;
                opt.textContent = `${s.round_name} - ${s.session_type_str} (${dateStr})`;
                sessionSelect.appendChild(opt);
            });
        }

        // Populate drivers selects
        const selects = ['#driver1', '#driver2', '#driver3'];
        const sortedDrivers = (data.drivers || []).slice().sort((a, b) => {
            const lastA = (a.short_name || a.full_name).toUpperCase();
            const lastB = (b.short_name || b.full_name).toUpperCase();
            return lastA.localeCompare(lastB);
        });

        selects.forEach((selId, idx) => {
            const selectEl = document.querySelector(selId);
            if (!selectEl) return;
            selectEl.innerHTML = `<option value="">${idx + 1}º Posición</option>`;
            sortedDrivers.forEach(d => {
                const opt = document.createElement('option');
                opt.value = d.driver_number;
                opt.textContent = d.short_name;
                opt.dataset.headshot = d.headshot_url || '';
                opt.dataset.colour = d.team_colour || 'ccc';
                opt.dataset.team = d.team || '';
                opt.dataset.fullname = d.full_name || d.short_name;
                selectEl.appendChild(opt);
            });

            selectEl.addEventListener('change', () => {
                updateDriverPreview(selId);
                validateDuplicateDrivers();
            });
        });

        function updateDriverPreview(selId) {
            const selectEl = document.querySelector(selId);
            const previewEl = document.getElementById('preview-' + selId.replace('#', ''));
            if (!selectEl || !previewEl) return;

            const opt = selectEl.selectedOptions[0];
            if (!selectEl.value || !opt) {
                previewEl.classList.remove('active');
                previewEl.innerHTML = '';
                return;
            }

            const headshot = opt.dataset.headshot;
            const colour = opt.dataset.colour || '999';
            const team = opt.dataset.team || '';
            const fullname = opt.dataset.fullname || opt.textContent;

            previewEl.innerHTML = `
                ${headshot ? `<img src="${headshot}" alt="${fullname}">` : ''}
                <div>
                    <div class="f1-preview-name">${fullname}</div>
                    ${team ? `<div class="f1-preview-team">${team}</div>` : ''}
                </div>
            `;
            previewEl.style.borderLeftColor = '#' + colour.replace('#', '');
            previewEl.classList.add('active');
        }

        function validateDuplicateDrivers() {
            const d1 = document.getElementById('driver1').value;
            const d2 = document.getElementById('driver2').value;
            const d3 = document.getElementById('driver3').value;

            if (d1 && d2 && d1 === d2) {
                alert('No puedes seleccionar el mismo piloto dos veces.');
                document.getElementById('driver2').value = '';
                updateDriverPreview('#driver2');
                return false;
            }
            if (d1 && d3 && d1 === d3) {
                alert('No puedes seleccionar el mismo piloto dos veces.');
                document.getElementById('driver3').value = '';
                updateDriverPreview('#driver3');
                return false;
            }
            if (d2 && d3 && d2 === d3) {
                alert('No puedes seleccionar el mismo piloto dos veces.');
                document.getElementById('driver3').value = '';
                updateDriverPreview('#driver3');
                return false;
            }
            return true;
        }

        // Form Submission
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const resultMsg = document.getElementById('bet-result');
            const submitBtn = form.querySelector('button[type="submit"]');

            if (!validateDuplicateDrivers()) return;

            const nick = document.getElementById('nick').value;
            const session_id = document.getElementById('session').value;
            const d1 = document.getElementById('driver1').value;
            const d2 = document.getElementById('driver2').value;
            const d3 = document.getElementById('driver3').value;

            if (!nick || !session_id || !d1 || !d2 || !d3) {
                resultMsg.className = 'f1-alert error';
                resultMsg.textContent = 'Por favor completa todos los campos del formulario.';
                resultMsg.style.display = 'block';
                return;
            }

            submitBtn.disabled = true;
            submitBtn.textContent = 'Enviando apuesta...';

            try {
                const res = await fetch('/api/submit-bet', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        nick: nick,
                        session_id: parseInt(session_id),
                        driver1_number: parseInt(d1),
                        driver2_number: parseInt(d2),
                        driver3_number: parseInt(d3)
                    })
                });
                const json = await res.json();
                if (json && json.success) {
                    resultMsg.className = 'f1-alert success';
                    resultMsg.textContent = '✓ ' + (json.message || 'Apuesta enviada correctamente.');
                    resultMsg.style.display = 'block';
                    // Clear driver selects
                    document.getElementById('driver1').value = '';
                    document.getElementById('driver2').value = '';
                    document.getElementById('driver3').value = '';
                    updateDriverPreview('#driver1');
                    updateDriverPreview('#driver2');
                    updateDriverPreview('#driver3');
                } else {
                    resultMsg.className = 'f1-alert error';
                    resultMsg.textContent = (json && json.message) || 'Error al guardar la apuesta.';
                    resultMsg.style.display = 'block';
                }
            } catch (err) {
                resultMsg.className = 'f1-alert error';
                resultMsg.textContent = 'Error de conexión con el servidor.';
                resultMsg.style.display = 'block';
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Enviar Apuesta';
            }
        });
    }

    // -------------------------------------------------------------------------
    // VER APUESTAS: Sessions & Bets breakdown
    // -------------------------------------------------------------------------
    async function initAllBets() {
        const container = document.getElementById('f1-all-bets-container');
        if (!container) return;

        const data = await fetchData();
        if (!data || !data.sessions) {
            container.innerHTML = '<p>No se encontraron apuestas disponibles.</p>';
            return;
        }

        const driversMap = {};
        (data.drivers || []).forEach(d => {
            driversMap[d.driver_number] = d;
            driversMap[d.short_name] = d;
        });

        // Map bets to session
        const betsBySession = {};
        (data.bets || []).forEach(b => {
            if (!betsBySession[b.session_id]) betsBySession[b.session_id] = [];
            betsBySession[b.session_id].push(b);
        });

        // Filter sessions that have bets or are upcoming
        // Sort newest sessions first
        const sessions = data.sessions.slice().reverse();

        container.innerHTML = '';
        sessions.forEach(sess => {
            const bets = betsBySession[sess.id] || [];
            if (bets.length === 0 && !sess.is_completed) return; // Don't show empty future sessions here

            const card = document.createElement('div');
            card.className = 'f1-session-bets';

            // Format date
            const d = new Date(sess.session_date);
            const dateStr = `${String(d.getDate()).padStart(2, '0')}/${String(d.getMonth() + 1).padStart(2, '0')}/${d.getFullYear()} ${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')}`;

            let resultsRow = '';
            if (sess.official_results && sess.official_results.length === 3) {
                resultsRow = `
                    <tr class="f1-result-row">
                        <td><strong>✅ Resultado</strong></td>
                        ${sess.official_results.map(dName => renderDriverCell(dName, driversMap, true)).join('')}
                        <td></td>
                    </tr>
                `;
            }

            const betsRows = bets.map(b => {
                const ptsText = b.points !== null && b.points !== undefined ? `<strong>${b.points}</strong>` : '<span class="f1-pts-empty">—</span>';
                return `
                    <tr>
                        <td><strong>${b.participant_nick}</strong></td>
                        ${renderDriverCell(b.driver1_number || b.driver1_name, driversMap)}
                        ${renderDriverCell(b.driver2_number || b.driver2_name, driversMap)}
                        ${renderDriverCell(b.driver3_number || b.driver3_name, driversMap)}
                        <td style="text-align:center;">${ptsText}</td>
                    </tr>
                `;
            }).join('');

            card.innerHTML = `
                <h3>🏁 ${sess.round_name} - ${sess.session_type_str}</h3>
                <p class="session-date">${dateStr}</p>
                <div class="f1-table-scroll">
                    <table class="f1-table f1-table-drivers">
                        <thead>
                            <tr>
                                <th>Participante</th>
                                <th>1º</th>
                                <th>2º</th>
                                <th>3º</th>
                                <th style="text-align:center;width:90px;">Puntos</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${resultsRow}
                            ${betsRows}
                        </tbody>
                    </table>
                </div>
            `;
            container.appendChild(card);
        });
    }

    function renderDriverCell(driverRef, driversMap, isBold = false) {
        const d = driversMap[driverRef] || {
            short_name: String(driverRef),
            team_colour: 'ccc',
            headshot_url: 'https://media.formula1.com/d_driver_fallback_image.png/content/dam/fom-website/drivers/M/MAXVER01_Max_Verstappen/maxver01.png.transform/1col/image.png'
        };

        const styleBar = d.team_colour ? `background:#${d.team_colour.replace('#','')};` : 'background:#ccc;';
        const nameDisplay = isBold ? `<strong>${d.short_name}</strong>` : d.short_name;

        return `
            <td>
                <div class="f1-driver-cell">
                    <span class="f1-team-bar" style="${styleBar}"></span>
                    <img class="f1-driver-thumb" src="${d.headshot_url}" alt="${d.short_name}" loading="lazy">
                    ${nameDisplay}
                </div>
            </td>
        `;
    }

    // -------------------------------------------------------------------------
    // CALENDARIO: 23 Grand Prix rounds
    // -------------------------------------------------------------------------
    async function initCalendar() {
        const grid = document.getElementById('f1-calendar-grid');
        if (!grid) return;

        const data = await fetchData();
        if (!data || !data.calendar) return;

        grid.innerHTML = '';
        data.calendar.forEach(item => {
            const card = document.createElement('div');
            card.className = 'f1-race-card';
            card.innerHTML = `
                <div class="race-round">${item.round}</div>
                <h3>${item.country}</h3>
                <p class="circuit">${item.circuit}</p>
                <p class="dates">${item.dates}</p>
            `;
            grid.appendChild(card);
        });
    }

    // -------------------------------------------------------------------------
    // AJUSTES: Admin management & Bet deletion
    // -------------------------------------------------------------------------
    async function initAjustes() {
        const container = document.getElementById('f1-ajustes-container');
        if (!container) return;

        const data = await fetchData();
        if (!data) return;

        const sessionFilter = document.getElementById('f1_filter_session');
        const nickFilter = document.getElementById('f1_filter_nick');

        // Populate session filter
        if (sessionFilter) {
            sessionFilter.innerHTML = '<option value="0">— Todas las sesiones —</option>';
            data.sessions.slice().reverse().forEach(s => {
                const opt = document.createElement('option');
                opt.value = s.id;
                const d = new Date(s.session_date);
                const dtStr = `${String(d.getDate()).padStart(2, '0')}/${String(d.getMonth() + 1).padStart(2, '0')}/${d.getFullYear()}`;
                const count = (data.bets || []).filter(b => b.session_id === s.id).length;
                opt.textContent = `${s.round_name} – ${s.session_type_str} (${dtStr}) · ${count} apuesta(s)`;
                sessionFilter.appendChild(opt);
            });
        }

        function renderAjustes() {
            const selectedSession = parseInt(sessionFilter.value) || 0;
            const selectedNick = nickFilter.value.trim();

            const driversMap = {};
            (data.drivers || []).forEach(d => {
                driversMap[d.driver_number] = d;
                driversMap[d.short_name] = d;
            });

            const betsBySession = {};
            (data.bets || []).forEach(b => {
                if (selectedNick && b.participant_nick !== selectedNick) return;
                if (!betsBySession[b.session_id]) betsBySession[b.session_id] = [];
                betsBySession[b.session_id].push(b);
            });

            container.innerHTML = '';
            const filteredSessions = data.sessions.filter(s => {
                if (selectedSession && s.id !== selectedSession) return false;
                return (betsBySession[s.id] && betsBySession[s.id].length > 0);
            }).reverse();

            if (filteredSessions.length === 0) {
                container.innerHTML = '<p style="padding:20px;text-align:center;color:#666;">No se encontraron apuestas con los filtros seleccionados.</p>';
                return;
            }

            filteredSessions.forEach(sess => {
                const bets = betsBySession[sess.id] || [];
                const d = new Date(sess.session_date);
                const dtStr = `${String(d.getDate()).padStart(2, '0')}/${String(d.getMonth() + 1).padStart(2, '0')}/${d.getFullYear()} ${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')}`;
                const badge = sess.is_completed ? '<span class="f1-status-badge completed">✅ Completada</span>' : '<span class="f1-status-badge pending">⏳ Pendiente</span>';

                const card = document.createElement('div');
                card.className = 'f1-bets-session-card';

                const rowsHtml = bets.map(b => {
                    const bDate = new Date(b.created_at);
                    const bDateStr = `${String(bDate.getDate()).padStart(2, '0')}/${String(bDate.getMonth() + 1).padStart(2, '0')}/${bDate.getFullYear()} ${String(bDate.getHours()).padStart(2, '0')}:${String(bDate.getMinutes()).padStart(2, '0')}`;
                    const ptsText = b.points !== null && b.points !== undefined ? `<strong class="f1-pts">${b.points} pts</strong>` : '<span class="f1-pts-empty">—</span>';

                    return `
                        <tr>
                            <td class="f1-bet-id">${b.id}</td>
                            <td><strong>${b.participant_nick}</strong></td>
                            ${renderDriverCell(b.driver1_number || b.driver1_name, driversMap)}
                            ${renderDriverCell(b.driver2_number || b.driver2_name, driversMap)}
                            ${renderDriverCell(b.driver3_number || b.driver3_name, driversMap)}
                            <td style="text-align:center;">${ptsText}</td>
                            <td style="font-size:0.85rem;color:#666;">${bDateStr}</td>
                            <td>
                                <button class="f1-btn f1-btn-danger f1-btn-sm" onclick="F1App.confirmDeleteBet(${b.id}, '${b.participant_nick}')">
                                    🗑️ Borrar
                                </button>
                            </td>
                        </tr>
                    `;
                }).join('');

                card.innerHTML = `
                    <div class="f1-session-header">
                        <div>
                            <h3>🏁 ${sess.round_name} — ${sess.session_type_str}</h3>
                            <p class="f1-session-meta">${dtStr} &nbsp;|&nbsp; ${badge}</p>
                        </div>
                    </div>
                    <div class="f1-table-scroll">
                        <table class="f1-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Participante</th>
                                    <th>1º</th>
                                    <th>2º</th>
                                    <th>3º</th>
                                    <th style="text-align:center;">Puntos</th>
                                    <th>Fecha apuesta</th>
                                    <th>Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${rowsHtml}
                            </tbody>
                        </table>
                    </div>
                `;
                container.appendChild(card);
            });
        }

        if (sessionFilter) sessionFilter.addEventListener('change', renderAjustes);
        if (nickFilter) nickFilter.addEventListener('change', renderAjustes);

        renderAjustes();
    }

    function confirmDeleteBet(betId, nick) {
        const modal = document.getElementById('f1-confirm-modal');
        const msg = document.getElementById('f1-confirm-msg');
        const okBtn = document.getElementById('f1-confirm-ok');
        if (!modal || !okBtn) return;

        msg.textContent = `¿Eliminar la apuesta #${betId} de ${nick}? Esta acción no se puede deshacer.`;
        okBtn.onclick = async () => {
            modal.style.display = 'none';
            try {
                const res = await fetch('/api/delete-bet', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ bet_id: betId })
                });
                const json = await res.json();
                if (json.success) {
                    alert('Apuesta eliminada.');
                    cachedData = null;
                    initAjustes();
                } else {
                    alert(json.message || 'Error al eliminar');
                }
            } catch (e) {
                alert('Error al contactar con el servidor.');
            }
        };

        modal.style.display = 'flex';
        modal.onclick = (e) => {
            if (e.target === modal) modal.style.display = 'none';
        };
    }

    // Auto-initialize on load
    document.addEventListener('DOMContentLoaded', () => {
        initNav();
        initStandings();
        initChart();
        initBetForm();
        initAllBets();
        initCalendar();
        initAjustes();
    });

    return {
        confirmDeleteBet: confirmDeleteBet
    };
})();
