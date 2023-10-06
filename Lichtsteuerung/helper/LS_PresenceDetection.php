<?php

/**
 * @project       Lichtsteuerung/helper
 * @file          LS_PresenceDetection.php
 * @author        Ulrich Bittner
 * @copyright     2023 Ulrich Bittner
 * @license       https://creativecommons.org/licenses/by-nc-sa/4.0/ CC BY-NC-SA 4.0
 */

/** @noinspection DuplicatedCode */

declare(strict_types=1);

trait LS_PresenceDetection
{
    /**
     * Executes the presence detection.
     *
     * @return void
     * @throws Exception
     */
    public function ExecutePresenceDetection(): void
    {
        $this->SendDebug(__FUNCTION__, 'wird ausgeführt', 0);
        if ($this->CheckMaintenance()) {
            return;
        }
        $id = $this->ReadPropertyInteger('Presence');
        $this->SendDebug(__FUNCTION__, 'Die Variable ' . $id . ' (Anwesenheit) hat sich geändert!', 0);
        $actualStatus = boolval(GetValue($id)); //false = absence, true = presence
        $statusName = 'Abwesenheit';
        $actionName = 'AbsenceAction';
        if ($actualStatus) { //Presence
            $statusName = 'Anwesenheit';
            $actionName = 'PresenceAction';
        }
        $this->SendDebug(__FUNCTION__, 'Aktueller Status: ' . $statusName, 0);
        $action = $this->CheckAction('Presence', $actionName);
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
                        ['type' => 3, 'condition' => $setting['CheckIsDay']],
                        ['type' => 4, 'condition' => $setting['CheckTwilight']]];
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
                    $this->SwitchLight($brightness);
                }
            }
        }
    }
}