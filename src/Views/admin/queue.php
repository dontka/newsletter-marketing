<?php
?><!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Queue d'envoi</title>
    <link rel="stylesheet" href="/public/assets/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="container navbar-container">
            <div class="navbar-brand">📧 Newsletter Admin</div>
            <button class="menu-toggle" type="button" aria-label="Ouvrir le menu">
                <span></span>
                <span></span>
                <span></span>
            </button>
            <ul class="navbar-menu">
                <li><a href="/admin/dashboard">Dashboard</a></li>
                <li><a href="/subscribers">Abonnés</a></li>
                <li><a href="/newsletter">Newsletters</a></li>
                <li><a href="/admin/queue" class="active">Queue</a></li>
                <li><a href="/admin/logout">Déconnexion</a></li>
            </ul>
        </div>
    </nav>

    <div class="container page-section">
        <div class="card">
            <div class="card-header flex-between">
                <div>
                    <p class="section-label">Traitement des jobs</p>
                    <h2>Queue d'envoi</h2>
                </div>
                <button id="process-queue-btn" type="button" class="btn btn-primary">Traiter la queue</button>
            </div>

            <div class="card-body">
                <p><strong>Jobs en attente :</strong> <span id="pending-jobs"><?= (int) ($pendingJobs ?? 0) ?></span></p>

                <div id="progress-shell" class="card" style="background:var(--light-bg); margin-top:1rem; display:none;">
                    <div class="card-header">
                        <h3>Progression de l'envoi</h3>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div style="display:flex; justify-content:space-between; margin-bottom:0.5rem;">
                                <strong id="progress-label">Prêt à démarrer</strong>
                                <span id="progress-count">0 / 0</span>
                            </div>
                            <div style="height:12px; background:#e5e7eb; border-radius:999px; overflow:hidden;">
                                <div id="progress-bar" style="height:100%; width:0%; background:linear-gradient(90deg,#667eea,#764ba2); transition:width 0.2s ease;"></div>
                            </div>
                        </div>
                        <pre id="queue-log" style="white-space:pre-wrap; word-break:break-word; max-height:280px; overflow:auto;"></pre>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="/public/assets/script.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const processBtn = document.getElementById('process-queue-btn');
            const progressShell = document.getElementById('progress-shell');
            const progressLabel = document.getElementById('progress-label');
            const progressCount = document.getElementById('progress-count');
            const progressBar = document.getElementById('progress-bar');
            const queueLog = document.getElementById('queue-log');
            const pendingJobsEl = document.getElementById('pending-jobs');

            let totalJobs = 0;
            let processedTotal = 0;

            function appendMessages(messages) {
                if (!Array.isArray(messages) || messages.length === 0) {
                    return;
                }

                const text = messages.join('\n');
                queueLog.textContent += (queueLog.textContent ? '\n' : '') + text;
                queueLog.scrollTop = queueLog.scrollHeight;
            }

            function updateProgress(processed, pending) {
                processedTotal += Number(processed || 0);
                const percent = totalJobs > 0 ? Math.min(100, Math.round((processedTotal / totalJobs) * 100)) : 0;
                progressLabel.textContent = pending > 0 ? 'Traitement en cours…' : 'Terminé';
                progressCount.textContent = processedTotal + ' / ' + totalJobs;
                progressBar.style.width = percent + '%';
                pendingJobsEl.textContent = pending;
            }

            async function processBatch(initial = false) {
                const body = new URLSearchParams();
                if (initial) {
                    body.append('initial', '1');
                }

                const response = await fetch('/admin/queue/process', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: body.toString(),
                });

                if (!response.ok) {
                    throw new Error('Erreur réseau');
                }

                return await response.json();
            }

            async function startQueue() {
                processBtn.disabled = true;
                progressShell.style.display = 'block';
                progressLabel.textContent = 'Initialisation...';
                queueLog.textContent = '';
                processedTotal = 0;
                totalJobs = 0;
                progressBar.style.width = '0%';
                progressCount.textContent = '0 / 0';

                try {
                    const result = await processBatch(true);
                    totalJobs = Number(result.totalJobs || result.pending || 0);
                    appendMessages(result.messages);
                    updateProgress(result.processed, result.pending);

                    if (!result.done) {
                        await continueProcessing();
                    } else {
                        progressLabel.textContent = 'Terminé';
                        progressBar.style.width = '100%';
                    }
                } catch (error) {
                    progressLabel.textContent = 'Erreur lors du traitement';
                    appendMessages([error.message]);
                } finally {
                    processBtn.disabled = false;
                }
            }

            async function continueProcessing() {
                while (true) {
                    const result = await processBatch();
                    appendMessages(result.messages);
                    updateProgress(result.processed, result.pending);

                    if (result.done || Number(result.pending) === 0) {
                        progressLabel.textContent = 'Terminé';
                        progressBar.style.width = '100%';
                        break;
                    }

                    await new Promise(resolve => setTimeout(resolve, 300));
                }
            }

            processBtn.addEventListener('click', startQueue);
        });
    </script>
</body>
</html>
