<?php

class SendJob
{
    public int $id;
    public int $newsletter_id;
    public int $subscriber_id;
    public string $status;
    public int $attempts;
    public ?string $last_error;
    public ?string $sent_at;
}
