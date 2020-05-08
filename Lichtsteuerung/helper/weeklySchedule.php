<?php

// Declare
declare(strict_types=1);

trait LS_weeklySchedule
{
    /**
     * Shows the actual action of the weekly schedule.
     */
    public function ShowActualWeeklyScheduleAction(): void
    {
        $this->SendDebug(__FUNCTION__, 'Die Methode wird ausgeführt. (' . microtime(true) . ')', 0);
        $warning = json_decode('"\u26a0\ufe0f"') . " Fehler\n\n"; // warning
        $okay = json_decode('"\u2705"') . " Aktuelle Aktion\n\n"; // white_check_mark
        $id = $this->ReadPropertyInteger('WeeklySchedule');
        if ($id == 0 || !@IPS_ObjectExists($id)) {
            echo $warning . 'Ein Wochenplan ist nicht vorhanden!';
            return;
        }
        if ($id != 0 && @IPS_ObjectExists($id)) {
            $event = IPS_GetEvent($id);
            if ($event['EventActive'] != 1) {
                echo $warning . 'Der Wochenplan ist inaktiv!';
                return;
            } else {
                $actionID = $this->DetermineAction();
                $actionName = $warning . '0 = keine Aktion gefunden!';
                $event = IPS_GetEvent($id);
                foreach ($event['ScheduleActions'] as $action) {
                    if ($action['ID'] === $actionID) {
                        $actionName = $okay . $actionID . ' = ' . $action['Name'];
                    }
                }
                echo $actionName;
            }
        }
    }

    /**
     * Triggers the action of the weekly schedule.
     */
    public function ExecuteWeeklyScheduleAction(): void
    {
        $this->SendDebug(__FUNCTION__, 'Die Methode wird ausgeführt. (' . microtime(true) . ')', 0);
        if ($this->CheckMaintenanceMode()) {
            return;
        }
        $this->SendDebug(__FUNCTION__, 'Der Wochenplan hat ausgelöst.', 0);
        // Check event plan
        if (!$this->ValidateWeeklySchedule()) {
            return;
        }
        $actionID = $this->DetermineAction();
        $variableName = 'WeeklySchedule';
        switch ($actionID) {
            // Off
            case 1:
                $actionName = 'WeeklyScheduleActionOne';
                $action = $this->CheckAction($variableName, $actionName);
                if (!$action) {
                    $this->SendDebug(__FUNCTION__, 'Abbruch, Wochenplanaktion: 1 = Aus hat keine aktivierten Aktionen!', 0);
                    return;
                }
                $this->SendDebug(__FUNCTION__, 'Wochenplanaktion: 1 = Aus', 0);
                break;

            // On
            case 2:
                $actionName = 'WeeklyScheduleActionTwo';
                $action = $this->CheckAction($variableName, $actionName);
                if (!$action) {
                    $this->SendDebug(__FUNCTION__, 'Abbruch, Wochenplanaktion: 2 = An hat keine aktivierten Aktionen!', 0);
                    return;
                }
                $this->SendDebug(__FUNCTION__, 'Wochenplanaktion: 2 = An', 0);
                break;

        }
        if (isset($actionName)) {
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

    //#################### Private

    /**
     * Determines the action from the weekly schedule.
     *
     * @return int
     * Returns the action id:
     * 1    = off
     * 2    = timer
     * 3    = on
     */
    private function DetermineAction(): int
    {
        $this->SendDebug(__FUNCTION__, 'Die Methode wird ausgeführt. (' . microtime(true) . ')', 0);
        $actionID = 0;
        if ($this->ValidateWeeklySchedule()) {
            $timestamp = time();
            $searchTime = date('H', $timestamp) * 3600 + date('i', $timestamp) * 60 + date('s', $timestamp);
            $weekDay = date('N', $timestamp);
            $id = $this->ReadPropertyInteger('WeeklySchedule');
            if ($id != 0 && @IPS_ObjectExists($id)) {
                $event = IPS_GetEvent($id);
                foreach ($event['ScheduleGroups'] as $group) {
                    if (($group['Days'] & pow(2, $weekDay - 1)) > 0) {
                        $points = $group['Points'];
                        foreach ($points as $point) {
                            $startTime = $point['Start']['Hour'] * 3600 + $point['Start']['Minute'] * 60 + $point['Start']['Second'];
                            if ($startTime <= $searchTime) {
                                $actionID = $point['ActionID'];
                            }
                        }
                    }
                }
            }
        }
        return $actionID;
    }

    /**
     * Validates the weekly schedule.
     *
     * @return bool
     * false    = failed
     * true     = valid
     */
    private function ValidateWeeklySchedule(): bool
    {
        $this->SendDebug(__FUNCTION__, 'Die Methode wird ausgeführt. (' . microtime(true) . ')', 0);
        $result = false;
        $id = $this->ReadPropertyInteger('WeeklySchedule');
        if ($id != 0 && @IPS_ObjectExists($id)) {
            $event = IPS_GetEvent($id);
            if ($event['EventActive'] == 1) {
                $result = true;
            }
        }
        if (!$result) {
            $this->SendDebug(__FUNCTION__, 'Abbruch, der Wochenplan ist nicht vorhanden oder deaktiviert!', 0);
        }
        return $result;
    }
}