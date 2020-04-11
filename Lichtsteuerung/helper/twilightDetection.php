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
        $id = $this->ReadPropertyInteger('TwilightStatus');
        $this->SendDebug(__FUNCTION__, 'Die Variable ' . $id . ' (Dämmerungsstatus) hat sich geändert!', 0);
        $actualStatus = boolval(GetValue($id)); // false = day, true = night
        $settings = json_decode($this->ReadPropertyString('TwilightDayAction'), true)[0];
        $statusName = 'Es ist Tag';
        if ($actualStatus) { // Night
            $settings = json_decode($this->ReadPropertyString('TwilightNightAction'), true)[0];
            $statusName = 'Es ist Nacht';
        }
        $this->SendDebug(__FUNCTION__, 'Aktueller Status: ' . $statusName, 0);
        if (!$settings['UseSettings']) {
            $this->SendDebug(__FUNCTION__, 'Abbruch, ' . $statusName . ' ist deaktiviert!', 0);
            return;
        }
        // Check conditions
        $conditions = [
            ['type' => 0, 'condition' => $settings['CheckAutomaticMode']],
            ['type' => 1, 'condition' => $settings['CheckLight']],
            ['type' => 2, 'condition' => $settings['CheckIsDay']],
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