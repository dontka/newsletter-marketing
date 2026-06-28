<?php

class Newsletter
{
    public int $id;
    public string $subject;
    public string $content;
    public ?string $plain_text;
    public string $created_by;
    public string $created_at;
    public ?string $scheduled_at;
    public string $status;
    public string $campaign_type = 'announcement';
    public string $audience = 'all';
    public int $tracking_enabled = 1;
}
