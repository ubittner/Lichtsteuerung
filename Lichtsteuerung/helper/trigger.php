<?php

// Declare
declare(strict_types=1);

trait LS_trigger
{
    /**
     * Checks a trigger variable for action.
     *
     * @param int $VariableID
     */
    public function CheckTrigger(int $VariableID): void
    {
        $this->SendDebug(__FUNCTION__, 'Die Methode wird ausgeführt. (' . microtime(true) . ')', 0);
        if ($this->CheckMaintenanceMode()) {
            return;
        }
        $settings = json_decode($this->ReadPropertyString('Triggers'), true);
        if (!empty($settings)) {
            foreach ($settings as $setting) {
                $id = $setting['ID'];
                $this->SendDebug(__FUNCTION__, 'ID: ' . $id, 0);
                if ($VariableID == $id) {
                    if ($setting['UseSettings']) {
                        $this->SendDebug(__FUNCTION__, 'Die Variable ' . $VariableID . ' wurde aktualisiert.', 0);
                        $actualValue = boolval(GetValue($VariableID));
                        $this->SendDebug(__FUNCTION__, 'Aktueller Wert: ' . json_encode($actualValue), 0);
                        $triggerValue = boolval($setting['TriggerValue']);
                        $this->SendDebug(__FUNCTION__, 'Auslösender Wert: ' . json_encode($triggerValue), 0);
                        // We have a trigger value
                        if ($actualValue == $triggerValue) {
                            $this->SendDebug(__FUNCTION__, 'Die Variable ' . $VariableID . ' hat ausgelöst.', 0);
                            // Check conditions
                            $this->SendDebug(__FUNCTION__, 'Settings: ' . json_encode($setting), 0);
                            $checkConditions = $this->CheckAllConditions(json_encode($setting));
                            $this->SendDebug(__FUNCTION__, 'Result checkConditions: ' . json_encode($checkConditions), 0);
                            if ($checkConditions) {
                                $this->SendDebug(__FUNCTION__, 'Alle Bedingungen  erfüllt!', 0);
                                // Check time
                                $checkTime = $this->CheckTimeCondition($setting['ExecutionTimeAfter'], $setting['ExecutionTimeBefore']);
                                if ($checkTime) {
                                    // Trigger action
                                    $this->TriggerExecutionDelay(intval($setting['ExecutionDelay']));
                                    $brightness = intval($setting['Brightness']);
                                    if ($setting['UpdateLastBrightness']) {
                                        $this->SetValue('LastBrightness', $brightness);
                                    }
                                    $dutyCycle = intval($setting['DutyCycle']);
                                    $dutyCycleUnit = intval($setting['DutyCycleUnit']);
                                    $this->SwitchLight($brightness, $dutyCycle, $dutyCycleUnit);
                                    return;
                                }
                            }
                        } else {
                            $this->SendDebug(__FUNCTION__, 'Die Variable ' . $VariableID . ' hat nicht ausgelöst.', 0);
                        }
                    }
                }
            }
        }
    }
}