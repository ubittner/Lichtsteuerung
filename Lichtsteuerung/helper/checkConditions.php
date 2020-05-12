<?php

// Declare
declare(strict_types=1);

trait LS_checkConditions
{
    //#################### Private

    /**
     * Checks all conditions.
     *
     * @param string $Settings
     * @return bool
     * false    = mismatch
     * true     = condition is valid
     *
     */
    private function CheckAllConditions(string $Settings): bool
    {
        $this->SendDebug(__FUNCTION__, 'Die Methode wird ausgeführt. (' . microtime(true) . ')', 0);
        // Check conditions
        $setting = json_decode($Settings, true);
        $conditions = [
            ['type' => 0, 'condition' => $setting['CheckAutomaticMode']],
            ['type' => 1, 'condition' => $setting['CheckSleepMode']],
            ['type' => 2, 'condition' => $setting['CheckLightMode']],
            ['type' => 3, 'condition' => $setting['CheckIsDay']],
            ['type' => 4, 'condition' => $setting['CheckTwilight']],
            ['type' => 5, 'condition' => $setting['CheckPresence']]];
        return $this->CheckConditions(json_encode($conditions));
    }

    /**
     * Checks the conditions.
     *
     * @param string $Conditions
     * 0    = automatic mode
     * 1    = sleep mode
     * 2    = light mode
     * 3    = is day
     * 4    = twilight
     * 5    = presence
     *
     * @return bool
     * false    = mismatch
     * true     = condition is valid
     */
    private function CheckConditions(string $Conditions): bool
    {
        $this->SendDebug(__FUNCTION__, 'Die Methode wird ausgeführt. (' . microtime(true) . ')', 0);
        $result = true;
        $Conditions = json_decode($Conditions, true);
        if (!empty($Conditions)) {
            $results = [];
            foreach ($Conditions as $condition) {
                switch ($condition['type']) {
                    // Automatic mode
                    case 0:
                        $checkAutomaticMode = $this->CheckAutomaticModeCondition($condition['condition']);
                        $results[$condition['type']] = $checkAutomaticMode;
                        break;

                    // Sleep mode
                    case 1:
                        $checkSleepMode = $this->CheckSleepModeCondition($condition['condition']);
                        $results[$condition['type']] = $checkSleepMode;
                        break;

                    // Light mode
                    case 2:
                        $checkLight = $this->CheckLightModeCondition($condition['condition']);
                        $results[$condition['type']] = $checkLight;
                        break;

                    // Is day
                    case 3:
                        $checkIsDay = $this->CheckIsDayCondition($condition['condition']);
                        $results[$condition['type']] = $checkIsDay;
                        break;

                    // Twilight
                    case 4:
                        $checkTwilight = $this->CheckTwilightCondition($condition['condition']);
                        $results[$condition['type']] = $checkTwilight;
                        break;

                    // Presence
                    case 5:
                        $checkPresence = $this->CheckPresenceCondition($condition['condition']);
                        $results[$condition['type']] = $checkPresence;
                        break;

                }
            }
            if (in_array(false, $results)) {
                $result = false;
            }
        }
        return $result;
    }

    /**
     * Checks the automatic mode condition.
     *
     * @param int $Condition
     * 0    = none
     * 1    = automatic mode must be off
     * 2    = automatic mode must be on
     *
     * @return bool
     * false    = mismatch
     * true     = condition is valid
     */
    private function CheckAutomaticModeCondition(int $Condition): bool
    {
        $this->SendDebug(__FUNCTION__, 'Die Methode wird ausgeführt. (' . microtime(true) . ')', 0);
        $result = true;
        $automaticMode = boolval($this->GetValue('AutomaticMode')); // false = automatic mode is off, true = automatic mode is on
        switch ($Condition) {
            // Automatic mode must be off
            case 1:
                if ($automaticMode) { // Automatic mode is on
                    $this->SendDebug(__FUNCTION__, 'Bedingung: 1 = Aus', 0);
                    $this->SendDebug(__FUNCTION__, 'Abbruch, die Automatik ist eingeschaltet!', 0);
                    $result = false;
                }
                break;

            // Automatic mode must be on
            case 2:
                if (!$automaticMode) { // Automatic mode is off
                    $this->SendDebug(__FUNCTION__, 'Bedingung: 2 = An', 0);
                    $this->SendDebug(__FUNCTION__, 'Abbruch, die Automatik ist ausgeschaltet!', 0);
                    $result = false;
                }
                break;

        }
        return $result;
    }

    /**
     * Checks the sleep mode condition.
     *
     * @param int $Condition
     * 0    = none
     * 1    = automatic mode must be off
     * 2    = automatic mode must be on
     *
     * @return bool
     * false    = mismatch
     * true     = condition is valid
     */
    private function CheckSleepModeCondition(int $Condition): bool
    {
        $this->SendDebug(__FUNCTION__, 'Die Methode wird ausgeführt. (' . microtime(true) . ')', 0);
        $result = true;
        $sleepMode = boolval($this->GetValue('SleepMode')); // false = sleep mode is off, true = sleep mode is on
        switch ($Condition) {
            // Sleep mode must be off
            case 1:
                if ($sleepMode) { // Sleep mode is on
                    $this->SendDebug(__FUNCTION__, 'Bedingung: 1 = Aus', 0);
                    $this->SendDebug(__FUNCTION__, 'Abbruch, der Ruhe-Modus ist eingeschaltet!', 0);
                    $result = false;
                }
                break;

            // Sleep mode must be on
            case 2:
                if (!$sleepMode) { // Sleep mode is off
                    $this->SendDebug(__FUNCTION__, 'Bedingung: 2 = An', 0);
                    $this->SendDebug(__FUNCTION__, 'Abbruch, der Ruhe-Modus ist ausgeschaltet!', 0);
                    $result = false;
                }
                break;

        }
        return $result;
    }

    /**
     * Checks the light condition.
     *
     * @param int $Condition
     * 0    = none
     * 1    = light must be off
     * 2    = timer must be on
     * 3    = timer or light must be on
     * 4    = light must be on
     *
     * @return bool
     * false    = mismatch
     * true     = condition is valid
     *
     */
    private function CheckLightModeCondition(int $Condition): bool
    {
        $this->SendDebug(__FUNCTION__, 'Die Methode wird ausgeführt. (' . microtime(true) . ')', 0);
        $result = true;
        $lightStatus = intval($this->GetValue('LightMode')); // false = light is off, true = light is on
        switch ($Condition) {
            // Light must be off
            case 1:
                if ($lightStatus == 1 || $lightStatus == 2) { // Timer or light is on
                    $this->SendDebug(__FUNCTION__, 'Bedingung: 1 = Aus', 0);
                    $this->SendDebug(__FUNCTION__, 'Abbruch, das Licht ist eingeschaltet!', 0);
                    $result = false;
                }
                break;

            // Timer must be on
            case 2:
                if ($lightStatus == 0 || $lightStatus == 2) { // Light is off or light is on
                    $this->SendDebug(__FUNCTION__, 'Bedingung: 2 = Timer', 0);
                    $this->SendDebug(__FUNCTION__, 'Abbruch, die Einschaltdauer ist nicht aktiv!', 0);
                    $result = false;
                }
                break;

            // Timer must be on or light must be on
            case 3:
                if ($lightStatus == 0) { // Light is off
                    $this->SendDebug(__FUNCTION__, 'Bedingung: 3 = Timer - An', 0);
                    $this->SendDebug(__FUNCTION__, 'Abbruch, das Licht ist ausgeschaltet!', 0);
                    $result = false;
                }
                break;

            // Light must be on
            case 4:
                if ($lightStatus == 0 || $lightStatus == 1) { // Light is off or timer is on
                    $this->SendDebug(__FUNCTION__, 'Bedingung: 4 =  An', 0);
                    $this->SendDebug(__FUNCTION__, 'Abbruch, das Licht ist nicht eingeschaltet!', 0);
                    $result = false;
                }
                break;

        }
        return $result;
    }

    /**
     * Checks the is day condition.
     *
     * @param int $Condition
     * 0    = none
     * 1    = must be night
     * 2    = must be day
     *
     * @return bool
     * false    = mismatch
     * true     = condition is valid
     */
    private function CheckIsDayCondition(int $Condition): bool
    {
        $this->SendDebug(__FUNCTION__, 'Die Methode wird ausgeführt. (' . microtime(true) . ')', 0);
        $result = true;
        switch ($Condition) {
            // Must be night
            case 1:
                $id = $this->ReadPropertyInteger('IsDay');
                if ($id == 0 || !@IPS_ObjectExists($id)) {
                    $this->SendDebug(__FUNCTION__, 'Abbruch, die Ist es Tag - Prüfung ist nicht konfiguriert oder vorhanden!', 0);
                    $result = false;
                }
                if ($id != 0 && @IPS_ObjectExists($id)) {
                    $isDayStatus = boolval(GetValue($id));
                    if ($isDayStatus) { // Day
                        $this->SendDebug(__FUNCTION__, 'Bedingung: 1 = Es ist Nacht', 0);
                        $this->SendDebug(__FUNCTION__, 'Abbruch, aktueller Status: Es ist Tag!', 0);
                        $result = false;
                    }
                }
                break;

            // Must be day
            case 2:
                $id = $this->ReadPropertyInteger('IsDay');
                if ($id == 0 || !@IPS_ObjectExists($id)) {
                    $this->SendDebug(__FUNCTION__, 'Abbruch, die Ist es Tag - Prüfung ist nicht konfiguriert oder vorhanden!', 0);
                    $result = false;
                }
                if ($id != 0 && @IPS_ObjectExists($id)) {
                    $isDayStatus = boolval(GetValue($id));
                    if (!$isDayStatus) { // Night
                        $this->SendDebug(__FUNCTION__, 'Bedingung: 2 = Es ist Tag', 0);
                        $this->SendDebug(__FUNCTION__, 'Abbruch, aktueller Status: Es ist Nacht!', 0);
                        $result = false;
                    }
                }
                break;
        }
        return $result;
    }

    /**
     * Checks the twilight condition.
     *
     * @param int $Condition
     * 0    = none
     * 1    = must be day
     * 2    = must be night
     *
     * @return bool
     * false    = mismatch
     * true     = condition is valid
     */
    private function CheckTwilightCondition(int $Condition): bool
    {
        $this->SendDebug(__FUNCTION__, 'Die Methode wird ausgeführt. (' . microtime(true) . ')', 0);
        $result = true;
        $id = $this->ReadPropertyInteger('TwilightStatus');
        switch ($Condition) {
            // Must be day
            case 1:
                if ($id == 0 || !@IPS_ObjectExists($id)) {
                    $this->SendDebug(__FUNCTION__, 'Abbruch, der Dämmerungsstatus ist nicht konfiguriert oder vorhanden!', 0);
                    $result = false;
                }
                if ($id != 0 && @IPS_ObjectExists($id)) {
                    $twilightStatus = boolval(GetValue($id));
                    if ($twilightStatus) { // Night
                        $this->SendDebug(__FUNCTION__, 'Bedingung: 1 = Es ist Tag', 0);
                        $this->SendDebug(__FUNCTION__, 'Abbruch, aktueller Status: Es ist Nacht!', 0);
                        $result = false;
                    }
                }
                break;

            // Must be night
            case 2:
                if ($id == 0 || !@IPS_ObjectExists($id)) {
                    $this->SendDebug(__FUNCTION__, 'Abbruch, der Dämmerungsstatus ist nicht konfiguriert oder vorhanden!', 0);
                    $result = false;
                }
                if ($id != 0 && @IPS_ObjectExists($id)) {
                    $twilightStatus = boolval(GetValue($id));
                    if (!$twilightStatus) { // Day
                        $this->SendDebug(__FUNCTION__, 'Bedingung: 2 = Es ist Nacht', 0);
                        $this->SendDebug(__FUNCTION__, 'Abbruch, aktueller Status: Es ist Tag!', 0);
                        $result = false;
                    }
                }
                break;
        }

        return $result;
    }

    /**
     * Checks the presence condition.
     *
     * @param int $Condition
     * 0    = none
     * 1    = status must be absence
     * 2    = status must be presence
     *
     * @return bool
     * false    = mismatch
     * true     = condition is valid
     */
    private function CheckPresenceCondition(int $Condition): bool
    {
        $this->SendDebug(__FUNCTION__, 'Die Methode wird ausgeführt. (' . microtime(true) . ')', 0);
        $result = true;
        $id = $this->ReadPropertyInteger('PresenceStatus');
        switch ($Condition) {
            // Must be absence
            case 1:
                if ($id == 0 || !@IPS_ObjectExists($id)) {
                    $this->SendDebug(__FUNCTION__, 'Abbruch, der Anwesenheitsstatus ist nicht konfiguriert oder vorhanden!', 0);
                    $result = false;
                }
                if ($id != 0 && @IPS_ObjectExists($id)) {
                    $presenceStatus = boolval(GetValue($id));
                    if ($presenceStatus) { // Presence
                        $this->SendDebug(__FUNCTION__, 'Bedingung: 1 = Abwesenheit', 0);
                        $this->SendDebug(__FUNCTION__, 'Abbruch, aktueller Status: Anwesenheit!', 0);
                        $result = false;
                    }
                }
                break;
            // Must be presence
            case 2:
                if ($id == 0 || !@IPS_ObjectExists($id)) {
                    $this->SendDebug(__FUNCTION__, 'Abbruch, der Anwesenheitsstatus ist nicht konfiguriert oder vorhanden!', 0);
                    $result = false;
                }
                if ($id != 0 && @IPS_ObjectExists($id)) {
                    $presenceStatus = boolval(GetValue($id));
                    if (!$presenceStatus) { // Absence
                        $this->SendDebug(__FUNCTION__, 'Bedingung: 2 = Anwesenheit', 0);
                        $this->SendDebug(__FUNCTION__, 'Abbruch, aktueller Status: Abwesenheit!', 0);
                        $result = false;
                    }
                }
                break;

        }
        return $result;
    }

    /**
     * Checks the execution time.
     *
     * @param string $ExecutionTimeAfter
     * @param string $ExecutionTimeBefore
     * @return bool
     * false    = mismatch
     * true     = condition is valid
     */
    private function CheckTimeCondition(string $ExecutionTimeAfter, string $ExecutionTimeBefore): bool
    {
        $this->SendDebug(__FUNCTION__, 'Die Methode wird ausgeführt. (' . microtime(true) . ')', 0);
        $result = true;
        // Actual time
        $actualTime = time();
        $this->SendDebug(__FUNCTION__, 'Aktuelle Uhrzeit: ' . date('H:i:s', $actualTime) . ', ' . $actualTime . ', ' . date('d.m.Y', $actualTime), 0);
        // Time after
        $timeAfter = json_decode($ExecutionTimeAfter);
        $timeAfterHour = $timeAfter->hour;
        $timeAfterMinute = $timeAfter->minute;
        $timeAfterSecond = $timeAfter->second;
        $timestampAfter = mktime($timeAfterHour, $timeAfterMinute, $timeAfterSecond, (int) date('n'), (int) date('j'), (int) date('Y'));
        // Time before
        $timeBefore = json_decode($ExecutionTimeBefore);
        $timeBeforeHour = $timeBefore->hour;
        $timeBeforeMinute = $timeBefore->minute;
        $timeBeforeSecond = $timeBefore->second;
        $timestampBefore = mktime($timeBeforeHour, $timeBeforeMinute, $timeBeforeSecond, (int) date('n'), (int) date('j'), (int) date('Y'));
        if ($timestampAfter != $timestampBefore) {
            $this->SendDebug(__FUNCTION__, 'Bedingung Uhrzeit nach: ' . date('H:i:s', $timestampAfter) . ', ' . $timestampAfter . ', ' . date('d.m.Y', $timestampAfter), 0);
            // Same day
            if ($timestampAfter <= $timestampBefore) {
                $this->SendDebug(__FUNCTION__, 'Bedingung Uhrzeit vor: ' . date('H:i:s', $timestampBefore) . ', ' . $timestampBefore . ', ' . date('d.m.Y', $timestampBefore), 0);
                $this->SendDebug(__FUNCTION__, 'Zeitraum ist am gleichen Tag', 0);
                if ($actualTime >= $timestampAfter && $actualTime <= $timestampBefore) {
                    $this->SendDebug(__FUNCTION__, 'Aktuelle Zeit liegt im definierten Zeitraum.', 0);
                } else {
                    $result = false;
                    $this->SendDebug(__FUNCTION__, 'Aktuelle Zeit liegt außerhalb des definierten Zeitraums.', 0);
                }
            } else { // Overnight
                if ($actualTime > $timestampBefore) {
                    $this->SendDebug(__FUNCTION__, 'Zeitraum erstreckt sich über zwei Tage.', 0);
                    $timestampBefore = mktime($timeBeforeHour, $timeBeforeMinute, $timeBeforeSecond, (int) date('n'), (int) date('j') + 1, (int) date('Y'));
                }
                $this->SendDebug(__FUNCTION__, 'Bedingung Uhrzeit vor: ' . date('H:i:s', $timestampBefore) . ', ' . $timestampBefore . ', ' . date('d.m.Y', $timestampBefore), 0);
                if ($actualTime >= $timestampAfter && $actualTime <= $timestampBefore) {
                    $this->SendDebug(__FUNCTION__, 'Aktuelle Zeit liegt im definierten Zeitraum.', 0);
                } else {
                    $this->SendDebug(__FUNCTION__, 'Aktuelle Zeit liegt außerhalb des definierten Zeitraum.', 0);
                    $result = false;
                }
            }
        } else {
            $this->SendDebug(__FUNCTION__, 'Aktuelle Zeit liegt im definierten Zeitraum.', 0);
        }
        return $result;
    }
}