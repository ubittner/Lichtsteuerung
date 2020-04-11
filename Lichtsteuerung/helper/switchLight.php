<?php

// Declare
declare(strict_types=1);

trait LS_switchLight
{
    /**
     * Switches the light to the brightness value.
     *
     * @param int $Brightness
     * @param int $DutyCycle
     * @param int $DutyCycleUnit
     * 0    = seconds
     * 1    = minutes
     */
    public function SwitchLight(int $Brightness, int $DutyCycle = 0, int $DutyCycleUnit = 0): void
    {
        $this->SendDebug(__FUNCTION__, 'Die Methode wird ausgeführt. (' . microtime(true) . ')', 0);
        $amount = $this->GetAmountOfLights();
        if ($amount == 0) {
            $this->SendDebug(__FUNCTION__, 'Abbruch, es sind keine zu schaltenden Lichter vorhanden!', 0);
            return;
        }
        $actualLightStatus = intval($this->GetValue('Light'));
        $actualDimmerValue = floatval($this->GetValue('Dimmer'));
        $actualDimmingPreset = intval($this->GetValue('DimmingPresets'));
        // Off
        if ($Brightness == 0) {
            $mode = 0;
            $modeText = 'ausgeschaltet (Aus)';
            $this->DeactivateDutyCycleTimer();
        }
        // Timer
        if ($Brightness > 0 && $DutyCycle != 0) {
            $mode = 1;
            $modeText = 'eingeschaltet (Timer)';

            if ($actualLightStatus == 0) {
                $this->SetDutyCycleTimer($DutyCycle, $DutyCycleUnit);
            } else {
                $this->SetDutyCycleTimer($DutyCycle, $DutyCycleUnit);
            }
        }
        // On
        if ($Brightness > 0 && $DutyCycle == 0) {
            $mode = 2;
            $modeText = 'eingeschaltet (Ein)';
            if ($actualLightStatus == 1) {
                $this->DeactivateDutyCycleTimer();
            }
        }
        if (isset($modeText)) {
            $this->SendDebug(__FUNCTION__, 'Alle Lichter werden ' . $modeText . '.', 0);
        }
        if (isset($mode)) {
            $this->SetValue('Light', $mode);
            $this->SetValue('Dimmer', $Brightness / 100);
            $profile = 'LS.' . $this->InstanceID . '.DimmingPresets';
            $associations = IPS_GetVariableProfile($profile)['Associations'];
            if (!empty($associations)) {
                $closestDimmingPreset = null;
                foreach ($associations as $association) {
                    if ($closestDimmingPreset === null || abs($Brightness - $closestDimmingPreset) > abs($association['Value'] - $Brightness)) {
                        $closestDimmingPreset = $association['Value'];
                    }
                }
            }
            if (isset($closestDimmingPreset)) {
                $this->SetValue('DimmingPresets', $closestDimmingPreset);
            }
            $lights = json_decode($this->ReadPropertyString('Lights'));
            $toggleStatus = [];
            $i = 0;
            foreach ($lights as $light) {
                if ($light->UseLight) {
                    $id = $light->ID;
                    if ($id != 0 && @IPS_ObjectExists($id)) {
                        $toggleStatus[$id] = true;
                        $i++;
                        $variableType = @IPS_GetVariable($id)['VariableType'];
                        switch ($variableType) {
                            // Boolean
                            case 0:
                                $actualVariableValue = boolval(GetValue($id));
                                $newVariableValue = boolval($Brightness);
                                break;

                            // Integer
                            case 1:
                                $actualVariableValue = intval(GetValue($id));
                                $newVariableValue = intval($Brightness);
                                break;

                            // Float
                            case 2:
                                $actualVariableValue = floatval(GetValue($id));
                                $newVariableValue = floatval($Brightness / 100);
                                break;
                        }
                        if (isset($actualVariableValue) && isset($newVariableValue)) {
                            if ($actualVariableValue == $newVariableValue) {
                                $this->SendDebug(__FUNCTION__, 'Abbruch, Die Variable ' . $id . ' hat bereits den Wert: ' . json_encode($newVariableValue) . '!', 0);
                                continue;
                            } else {
                                $this->SendDebug(__FUNCTION__, 'Variable ' . $id . ', neuer Wert: ' . $newVariableValue . ', Helligkeit: ' . json_encode($Brightness) . '%', 0);
                                $toggle = @RequestAction($id, $newVariableValue);
                                if (!$toggle) {
                                    IPS_Sleep(self::DEVICE_DELAY_MILLISECONDS);
                                    $toggleAgain = @RequestAction($id, $newVariableValue);
                                    if (!$toggleAgain) {
                                        $toggleStatus[$id] = false;
                                        if (isset($modeText)) {
                                            $this->SendDebug(__FUNCTION__, 'Fehler, das Licht mit der ID ' . $id . ' konnte nicht ' . $modeText . ' werden!', 0);
                                            IPS_LogMessage(__FUNCTION__, 'Fehler, das Licht mit der ID ' . $id . ' konnte nicht ' . $modeText . ' werden!');
                                        }
                                    }
                                }
                                if ($i < $amount) {
                                    $this->SendDebug(__FUNCTION__, 'Die Verzögerung wird ausgeführt.', 0);
                                    IPS_Sleep(self::DEVICE_DELAY_MILLISECONDS);
                                }
                            }
                        }
                    }
                }
            }
            if (!in_array(true, $toggleStatus)) {
                // Revert switch
                $this->SetValue('Light', $actualLightStatus);
                $this->SetValue('Dimmer', $actualDimmerValue);
                $this->SetValue('DimmingPreset', $actualDimmingPreset);
            }
            if (in_array(true, $toggleStatus)) {
                if (isset($modeText)) {
                    $this->SendDebug(__FUNCTION__, 'Die Lichter wurden ' . $modeText . '.', 0);
                }
            }
        }
    }

    //##################### Private

    /**
     * Gets the amount of lights.
     *
     * @return int
     */
    private function GetAmountOfLights(): int
    {
        $amount = 0;
        $lights = json_decode($this->ReadPropertyString('Lights'));
        if (!empty($lights)) {
            foreach ($lights as $light) {
                if ($light->UseLight) {
                    $id = $light->ID;
                    if ($id != 0 && @IPS_ObjectExists($id)) {
                        $amount++;
                    }
                }
            }
        }
        return $amount;
    }

    /**
     * Registers the duty cycle timer.
     */
    private function RegisterDutyCycleTimer(): void
    {
        $this->RegisterTimer('SwitchLightOff', 0, 'LS_SwitchLight(' . $this->InstanceID . ', 0, 0, 0);');
    }

    /**
     * Sets the interval for the duty cycle timer.
     *
     * @param int $DutyCycle
     * @param int $DutyCycleUnit
     * 0    = seconds
     * 1    = minutes
     */
    private function SetDutyCycleTimer(int $DutyCycle, int $DutyCycleUnit): void
    {
        $this->SendDebug(__FUNCTION__, 'Die Methode wird ausgeführt. (' . microtime(true) . ')', 0);
        if ($DutyCycleUnit == 1) {
            $DutyCycle = $DutyCycle * 60;
        }
        $this->SetTimerInterval('SwitchLightOff', $DutyCycle * 1000);
        $timestamp = time() + $DutyCycle;
        $this->SetValue('DutyCycleInfo', date('d.m.Y, H:i:s', ($timestamp)));
        $this->SendDebug(__FUNCTION__, 'Die Einschaltdauer wurde festgelegt.', 0);
    }

    /**
     * Deactivates the duty cycle timer.
     */
    private function DeactivateDutyCycleTimer(): void
    {
        $this->SendDebug(__FUNCTION__, 'Die Methode wird ausgeführt. (' . microtime(true) . ')', 0);
        $this->SetTimerInterval('SwitchLightOff', 0);
        $this->SetValue('DutyCycleInfo', '-');
        $this->SendDebug(__FUNCTION__, 'Der Timer wurde deaktiviert.', 0);
    }

    /**
     * Triggers an execution delay.
     *
     * @param int $Delay
     */
    private function TriggerExecutionDelay(int $Delay): void
    {
        if ($Delay != 0) {
            $this->SendDebug(__FUNCTION__, 'Die Verzögerung von ' . $Delay . ' Sekunden wird ausgeführt.', 0);
            IPS_Sleep($Delay * 1000);
        }
    }
}