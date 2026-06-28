<?php

require_once __DIR__ . '/../Lib/DB.php';

class TrackingController
{
    public function open(): void
    {
        $jobId = $_GET['job'] ?? null;
        if (!$jobId) {
            $this->sendPixel();
            return;
        }

        try {
            $pdo = DB::getConnection();
            $insert = $pdo->prepare('
                INSERT INTO events (send_job_id, type, created_at)
                VALUES (:send_job_id, :type, :created_at)
            ');
            $insert->execute([
                'send_job_id' => $jobId,
                'type' => 'open',
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (Exception $e) {
            // Silently fail to not break email display
        }

        $this->sendPixel();
    }

    public function click(): void
    {
        $url = $_GET['u'] ?? null;
        $jobId = $_GET['job'] ?? null;

        if (!$url) {
            header('HTTP/1.0 404 Not Found');
            exit;
        }

        try {
            $pdo = DB::getConnection();
            $insert = $pdo->prepare('
                INSERT INTO events (send_job_id, type, meta, created_at)
                VALUES (:send_job_id, :type, :meta, :created_at)
            ');
            $insert->execute([
                'send_job_id' => $jobId ?: null,
                'type' => 'click',
                'meta' => json_encode(['url' => $url]),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (Exception $e) {
            // Silently fail
        }

        header('Location: ' . $url);
        exit;
    }

    private function sendPixel(): void
    {
        header('Content-Type: image/gif');
        header('Content-Length: 43');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        echo base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
        exit;
    }
}
