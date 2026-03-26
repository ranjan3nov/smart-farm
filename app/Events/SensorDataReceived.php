<?php

namespace App\Events;

use App\Models\SensorReading;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SensorDataReceived implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly SensorReading $reading) {}

    /** @return array<int, Channel> */
    public function broadcastOn(): array
    {
        return [new Channel('farm')];
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->reading->id,
            'moisture' => $this->reading->moisture,
            'moisture_percent' => $this->reading->moisture_percent,
            'soil_status' => $this->reading->soil_status,
            'soil_color' => $this->reading->soil_color,
            'rain' => $this->reading->rain,
            'rain_percent' => $this->reading->rain_percent,
            'rain_status' => $this->reading->rain_status,
            'temp' => $this->reading->temp,
            'humidity' => $this->reading->humidity,
            'water_dist' => $this->reading->water_dist,
            'tank_fill_percent' => $this->reading->tank_fill_percent,
            'tank_status' => $this->reading->tank_status,
            'pump_command' => $this->reading->pump_command,
            'ai_reason' => $this->reading->ai_reason,
            'created_at' => $this->reading->created_at->toISOString(),
        ];
    }
}
