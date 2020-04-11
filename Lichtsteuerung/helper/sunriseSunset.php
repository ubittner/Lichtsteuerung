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
        $modeName = 'Sonnenaufgang';
        $settings = json_decode($this->ReadPropertyString('Sunrise'), true)[0];
        if ($Mode == 1) {
            $modeName = 'Sonnenuntergang';
            $settings = json_decode($this->ReadPropertyString('Sunset'), true)[0];
        }
        $this->SendDebug(__FUNCTION__, 'Die Variable ' . $VariableID . ' (' . $modeName . ') hat sich geändert!', 0);
        if (!$settings['UseSettings']) {
            $this->SendDebug(__FUNCTION__, 'Abbruch, die Variable ' . $modeName . ' ist deaktiviert!', 0);
            return;
        }
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
        $brightness = intval($settings['Brightness']);
        $dutyCycle = intval($settings['DutyCycle']);
        $dutyCycleUnit = intval($settings['DutyCycleUnit']);
        $this->SwitchLight($brightness, $dutyCycle, $dutyCycleUnit);
    }
}