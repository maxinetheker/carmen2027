<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\NotificationSettingRules;
use App\Http\Resources\NotificationSettingResource;
use App\Models\NotificationSetting;
use App\Services\CrmReminderDispatcher;
use Illuminate\Http\Request;

class NotificationSettingController extends Controller
{
    public function show()
    {
        return new NotificationSettingResource(NotificationSetting::current());
    }

    public function update(Request $request)
    {
        $data = NotificationSettingRules::withBooleans(
            $request->validate($this->rules()), $request
        );
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
                ? 'Se enviaron '.count($sent).' aviso(s).'
                : 'Revisado: en este momento no hay nada que avisar.',
        ]);
    }

    private function rules(): array
    {
        return NotificationSettingRules::shared() + [
            'recipient_emails' => ['required', 'array', 'min:1', 'max:10'],
            'recipient_emails.*' => ['email'],
        ];
    }
}
