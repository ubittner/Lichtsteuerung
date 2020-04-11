<?php

// Declare
declare(strict_types=1);

trait LS_presenceDetection
{
    /**
     * Executes the presence detection.
     */
    public function ExecutePresenceDetection(): void
    {
        $this->SendDebug(__FUNCTION__, 'Die Methode wird ausgeführt. (' . microtime(true) . ')', 0);
        $id = $this->ReadPropertyInteger('PresenceStatus');
        $this->SendDebug(__FUNCTION__, 'Die Variable ' . $id . ' (Anwesenheitsstatus) hat sich geändert!', 0);
        $actualStatus = boolval(GetValue($id)); // false = absence, true = presence
        $settings = json_decode($this->ReadPropertyString('AbsenceAction'), true)[0];
        $statusName = 'Abwesenheit';
        if ($actualStatus) { // Presence
            $settings = json_decode($this->ReadPropertyString('PresenceAction'), true)[0];
            $statusName = 'Anwesenheit';
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
            ['type' => 3, 'condition' => $settings['CheckTwilight']]];
        $checkConditions = $this->CheckConditions(json_encode($conditions));
        if (!$checkConditions) {
            return;
        }
        // Trigger action
        $this->TriggerExecutionDelay(intval($settings['ExecutionDelay']));
        $brightness = intval($settings['Brightness']);
        $dutyCycle = intval($settings['DutyCycle']);
        $dutyCycleUnit = intval($settings['DutyCycleUnit']);
        $this->SwitchLight($brightness, $dutyCycle, $dutyCycleUnit);
    }
}