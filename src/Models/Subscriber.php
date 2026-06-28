<?php

class Subscriber
{
    public int $id;
    public string $email;
    public ?string $name;
    public string $token;
    public string $status;
    public string $created_at;
    public ?string $confirmed_at;
    public ?string $unsubscribed_at;
}
