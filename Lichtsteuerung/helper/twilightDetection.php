<?php

// Declare
declare(strict_types=1);

trait LS_twilightDetection
{
    /**
     * Executes the is day detection.
     */
    public function ExecuteTwilightDetection(): void
    {
        $this->SendDebug(__FUNCTION__, 'Die Methode wird ausgeführt. (' . microtime(true) . ')', 0);
        if ($this->CheckMaintenanceMode()) {
            return;
        }
        $id = $this->ReadPropertyInteger('TwilightStatus');
        $this->SendDebug(__FUNCTION__, 'Die Variable ' . $id . ' (Dämmerungsstatus) hat sich geändert!', 0);
        $actualStatus = boolval(GetValue($id)); // false = day, true = night
        $statusName = 'Es ist Tag';
        $actionName = 'TwilightDayAction';
        if ($actualStatus) { // Night
            $statusName = 'Es ist Nacht';
            $actionName = 'TwilightNightAction';
        }
        $this->SendDebug(__FUNCTION__, 'Aktueller Status: ' . $statusName, 0);
        $action = $this->CheckAction('TwilightStatus', $actionName);
        if (!$action) {
            $this->SendDebug(__FUNCTION__, 'Abbruch, die Variable ' . $statusName . ' hat keine aktivierten Aktionen!', 0);
            return;
        }
        $settings = json_decode($this->ReadPropertyString($actionName), true);
        if (!empty($settings)) {
            foreach ($settings as $setting) {
                if ($setting['UseSettings']) {
                    // Check conditions
                    $conditions = [
                        ['type' => 0, 'condition' => $setting['CheckAutomaticMode']],
                        ['type' => 1, 'condition' => $setting['CheckSleepMode']],
                        ['type' => 2, 'condition' => $setting['CheckLightMode']],
                        ['type' => 3, 'condition' => $setting['CheckIsDay']],
                        ['type' => 5, 'condition' => $setting['CheckPresence']]];
                    $checkConditions = $this->CheckConditions(json_encode($conditions));
                    if (!$checkConditions) {
                        continue;
                    }
                    // Trigger action
                    $brightness = $setting['Brightness'];
                    if ($setting['UpdateLastBrightness']) {
                        $this->SetValue('LastBrightness', $brightness);
                    }
                    $this->TriggerExecutionDelay(intval($setting['ExecutionDelay']));
                    $this->SwitchLight($brightness, 0, 0);
                }
            }
        }
    }
}