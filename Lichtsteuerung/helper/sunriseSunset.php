<?php

// Declare
declare(strict_types=1);

trait LS_sunriseSunset
{
    /**
     * Execute the sunrise or sunset action.
     *
     * @param int $VariableID
     * @param int $Mode
     * 0    = sunrise
     * 1    = sunset
     */
    public function ExecuteSunriseSunsetAction(int $VariableID, int $Mode): void
    {
        $this->SendDebug(__FUNCTION__, 'Die Methode wird ausgeführt. (' . microtime(true) . ')', 0);
        if ($this->CheckMaintenanceMode()) {
            return;
        }
        $modeName = 'Sonnenaufgang';
        $variableName = 'Sunrise';
        $actionName = 'SunriseAction';
        if ($Mode == 1) {
            $modeName = 'Sonnenuntergang';
            $variableName = 'Sunset';
            $actionName = 'SunsetAction';
        }
        $this->SendDebug(__FUNCTION__, 'Die Variable ' . $VariableID . ' (' . $modeName . ') hat sich geändert!', 0);
        $action = $this->CheckAction($variableName, $actionName);
        if (!$action) {
            $this->SendDebug(__FUNCTION__, 'Abbruch, die Variable ' . $modeName . ' hat keine aktivierten Aktionen!', 0);
            return;
        }
        $settings = json_decode($this->ReadPropertyString($actionName), true);
        if (!empty($settings)) {
            foreach ($settings as $setting) {
                if ($setting['UseSettings']) {
                    // Check conditions
                    $checkConditions = $this->CheckAllConditions(json_encode($setting));
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