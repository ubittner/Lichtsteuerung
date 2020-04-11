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

    //#################### Private

    /**
     * Triggers the action of the weekly schedule.
     */
    public function ExecuteWeeklyScheduleAction(): void
    {
        $this->SendDebug(__FUNCTION__, 'Die Methode wird ausgeführt. (' . microtime(true) . ')', 0);
        $this->SendDebug(__FUNCTION__, 'Der Wochenplan hat ausgelöst.', 0);
        // Check event plan
        if (!$this->ValidateWeeklySchedule()) {
            return;
        }
        $actionID = $this->DetermineAction();
        switch ($actionID) {
            // Off
            case 1:
                $settings = json_decode($this->ReadPropertyString('WeeklyScheduleActionOne'), true)[0];
                if (!$settings['UseSettings']) {
                    $this->SendDebug(__FUNCTION__, 'Abbruch, Wochenplanaktion: 1 = Aus ist deaktiviert!', 0);
                    return;
                }
                $this->SendDebug(__FUNCTION__, 'Wochenplanaktion: 1 = Aus', 0);
                break;

            // Timer
            case 2:
                $settings = json_decode($this->ReadPropertyString('WeeklyScheduleActionTwo'), true)[0];
                if (!$settings['UseSettings']) {
                    $this->SendDebug(__FUNCTION__, 'Abbruch, Wochenplanaktion: 2 = Timer ist deaktiviert!', 0);
                    return;
                }
                $this->SendDebug(__FUNCTION__, 'Wochenplanaktion: 2 = Timer', 0);
                break;

            // On
            case 3:
                $settings = json_decode($this->ReadPropertyString('WeeklyScheduleActionThree'), true)[0];
                if (!$settings['UseSettings']) {
                    $this->SendDebug(__FUNCTION__, 'Abbruch, Wochenplanaktion: 3 = An ist deaktiviert!', 0);
                    return;
                }
                $this->SendDebug(__FUNCTION__, 'Wochenplanaktion: 3 = An', 0);
                break;
        }
        if (isset($settings)) {
            // Check conditions
            $conditions = [
                ['type' => 0, 'condition' => $settings['CheckAutomaticMode']],
                ['type' => 1, 'condition' => $settings['CheckLight']],
                ['type' => 2, 'condition' => $settings['CheckIsDay']],
                ['type' => 3, 'condition' => $settings['CheckTwilight']],
                ['type' => 4, 'condition' => $settings['CheckPresence']]];
            $checkConditions = $this->CheckConditions(json_encode($conditions));
            if (!$checkConditions) {
                return;
            }
            // Trigger action
            $this->TriggerExecutionDelay(intval($settings['ExecutionDelay']));
            $brightness = $settings['Brightness'];
            $dutyCycle = $settings['DutyCycle'];
            $dutyCycleUnit = $settings['DutyCycleUnit'];
            $this->SwitchLight($brightness, $dutyCycle, $dutyCycleUnit);
        }
    }

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