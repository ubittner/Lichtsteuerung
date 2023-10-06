<?php

/**
 * @project       Lichtsteuerung/helper
 * @file          LS_SunriseSunset.php
 * @author        Ulrich Bittner
 * @copyright     2023 Ulrich Bittner
 * @license       https://creativecommons.org/licenses/by-nc-sa/4.0/ CC BY-NC-SA 4.0
 */

/** @noinspection DuplicatedCode */

declare(strict_types=1);

trait LS_SunriseSunset
{
    /**
     * Execute the sunrise or sunset action.
     *
     * @param int $VariableID
     * @param int $Mode
     * 0 =  sunrise,
     * 1 =  sunset
     *
     * @return void
     * @throws Exception
     */
    public function ExecuteSunriseSunsetAction(int $VariableID, int $Mode): void
    {
        $this->SendDebug(__FUNCTION__, 'wird ausgeführt', 0);
        if ($this->CheckMaintenance()) {
            return;
        }
        $modeName = 'Sonnenaufgang';
        $variableName = 'Sunrise';
        $actionName = 'SunriseActions';
        if ($Mode == 1) {
            $modeName = 'Sonnenuntergang';
            $variableName = 'Sunset';
            $actionName = 'SunsetActions';
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
                    //Check conditions
                    $checkConditions = $this->CheckAllConditions(json_encode($setting));
                    if (!$checkConditions) {
                        continue;
                    }
                    //Trigger action
                    $brightness = $setting['Brightness'];
                    if ($setting['UpdateLastBrightness']) {
                        $this->SetValue('LastBrightness', $brightness);
                    }
                    $this->TriggerExecutionDelay(intval($setting['ExecutionDelay']));
                    $this->SwitchLight($brightness);
                }
            }
        }
    }
}