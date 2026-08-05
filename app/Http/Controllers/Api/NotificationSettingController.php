<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationSettingResource;
use App\Models\NotificationSetting;
use App\Services\CrmReminderDispatcher;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class NotificationSettingController extends Controller
{
    public function show()
    {
        return new NotificationSettingResource(NotificationSetting::current());
    }

    public function update(Request $request)
    {
        $data = $request->validate($this->rules());
        foreach (['follow_up', 'appointment', 'task'] as $key) {
            $data["{$key}_enabled"] = $request->boolean("{$key}_enabled");
        }
        foreach (['appointment', 'task'] as $key) {
            $data["{$key}_immediate_enabled"] = $request->boolean("{$key}_immediate_enabled");
        }
        $emails = array_values(array_unique(array_map('strtolower', $data['recipient_emails'])));
        $data['recipient_emails'] = $emails;
        $data['recipient_email'] = $emails[0];
        NotificationSetting::current()->update($data);

        return new NotificationSettingResource(NotificationSetting::current());
    }

    public function run(CrmReminderDispatcher $dispatcher)
    {
        $sent = $dispatcher->run(true);

        return response()->json([
            'sent' => $sent,
            'message' => $sent
                ? 'Se procesaron los avisos activos.'
                : 'No hay registros que requieran un aviso en este momento.',
        ]);
    }

    private function rules(): array
    {
        $rules = [
            'recipient_emails' => ['required', 'array', 'min:1', 'max:10'],
            'recipient_emails.*' => ['email'],
            'timezone' => ['required', Rule::in(['America/Lima', 'America/Bogota',
                'America/Mexico_City', 'America/New_York'])],
        ];
        foreach (['follow_up', 'appointment', 'task'] as $key) {
            $rules["{$key}_enabled"] = ['nullable', 'boolean'];
            $rules["{$key}_frequency"] = ['required', Rule::in(['daily', 'weekly'])];
            $rules["{$key}_time"] = ['required', 'date_format:H:i'];
            $rules["{$key}_weekday"] = ['required', 'integer', 'between:1,7'];
            $rules["{$key}_days"] = ['required', 'integer', 'between:0,365'];
        }
        foreach (['appointment', 'task'] as $key) {
            $rules["{$key}_immediate_enabled"] = ['nullable', 'boolean'];
            $rules["{$key}_lead_minutes"] = ['required', 'integer', 'between:5,10080'];
        }

        return $rules;
    }
}
