<?php

// Declare
declare(strict_types=1);

trait LS_checkConditions
{
    /**
     * Checks the conditions.
     *
     * @param string $Conditions
     * 0    = automatic mode
     * 1    = light
     * 2    = is day
     * 3    = twilight
     * 4    = presence
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

                    // Light
                    case 1:
                        $checkLight = $this->CheckLightCondition($condition['condition']);
                        $results[$condition['type']] = $checkLight;
                        break;

                    // Is day
                    case 2:
                        $checkIsDay = $this->CheckIsDayCondition($condition['condition']);
                        $results[$condition['type']] = $checkIsDay;
                        break;

                    // Twilight
                    case 3:
                        $checkTwilight = $this->CheckTwilightCondition($condition['condition']);
                        $results[$condition['type']] = $checkTwilight;
                        break;

                    // Presence
                    case 4:
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
    private function CheckLightCondition(int $Condition): bool
    {
        $this->SendDebug(__FUNCTION__, 'Die Methode wird ausgeführt. (' . microtime(true) . ')', 0);
        $result = true;
        $lightStatus = intval($this->GetValue('Light')); // false = light is off, true = light is on
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
}