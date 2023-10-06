<?php

/**
 * @project       Lichtsteuerung/helper
 * @file          LS_IsDayDetection.php
 * @author        Ulrich Bittner
 * @copyright     2023 Ulrich Bittner
 * @license       https://creativecommons.org/licenses/by-nc-sa/4.0/ CC BY-NC-SA 4.0
 */

/** @noinspection DuplicatedCode */

declare(strict_types=1);

trait LS_IsDayDetection
{
    /**
     * Executes the is day detection.
     *
     * @return void
     * @throws Exception
     */
    public function ExecuteIsDayDetection(): void
    {
        $this->SendDebug(__FUNCTION__, 'wird ausgeführt', 0);
        if ($this->CheckMaintenance()) {
            return;
        }
        $id = $this->ReadPropertyInteger('IsDay');
        $this->SendDebug(__FUNCTION__, 'Die Variable ' . $id . ' (Ist es Tag) hat sich geändert!', 0);
        $actualStatus = boolval(GetValue($id)); //false = night, true = day
        $statusName = 'Es ist Nacht';
        $actionName = 'NightAction';
        if ($actualStatus) {
            $statusName = 'Es ist Tag';
            $actionName = 'DayAction';
        }
        $this->SendDebug(__FUNCTION__, 'Aktueller Status: ' . $statusName, 0);
        $action = $this->CheckAction('IsDay', $actionName);
        if (!$action) {
            $this->SendDebug(__FUNCTION__, 'Abbruch, die Variable ' . $statusName . ' hat keine aktivierten Aktionen!', 0);
            return;
        }
        $settings = json_decode($this->ReadPropertyString($actionName), true);
        if (!empty($settings)) {
            foreach ($settings as $setting) {
                if ($setting['UseSettings']) {
                    //Check conditions
                    $conditions = [
                        ['type' => 0, 'condition' => $setting['CheckAutomaticMode']],
                        ['type' => 1, 'condition' => $setting['CheckSleepMode']],
                        ['type' => 2, 'condition' => $setting['CheckLightMode']],
                        ['type' => 4, 'condition' => $setting['CheckTwilight']],
                        ['type' => 5, 'condition' => $setting['CheckPresence']]];
                    $checkConditions = $this->CheckConditions(json_encode($conditions));
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