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
     *
     * @return bool
     */
    public function SwitchLight(int $Brightness, int $DutyCycle = 0, int $DutyCycleUnit = 0): bool
    {
        $this->SendDebug(__FUNCTION__, 'Die Methode wird ausgeführt. (' . microtime(true) . ')', 0);
        $result = false;
        if ($this->CheckMaintenanceMode()) {
            return $result;
        }
        $id = $this->ReadPropertyInteger('Light');
        if ($id == 0 || !@IPS_ObjectExists($id)) {
            $this->SendDebug(__FUNCTION__, 'Abbruch, es ist kein Rollladenaktor vorhanden!', 0);
            return $result;
        }
        $actualLightMode = intval($this->GetValue('LightMode'));
        $actualDimmerValue = intval($this->GetValue('Dimmer'));
        $actualDimmingPreset = intval($this->GetValue('DimmingPresets'));
        $actualLastBrightness = intval($this->GetValue('LastBrightness'));
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
            $this->SetDutyCycleTimer($DutyCycle, $DutyCycleUnit);
        }
        // On
        if ($Brightness > 0 && $DutyCycle == 0) {
            $mode = 2;
            $modeText = 'eingeschaltet (Ein)';
            if ($actualLightMode == 1) {
                $this->DeactivateDutyCycleTimer();
            }
        }
        if ($DutyCycle == 0) {
            $this->DeactivateDutyCycleTimer();
        }
        if (isset($modeText)) {
            $this->SendDebug(__FUNCTION__, 'Alle Lichter werden ' . $modeText . '.', 0);
        }
        if (isset($mode)) {
            $this->SetValue('LightMode', $mode);
            $this->SetValue('Dimmer', $Brightness);
            $this->SetClosestDimmingPreset($Brightness);
            if ($id != 0 && @IPS_ObjectExists($id)) {
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
                    } else {
                        $this->SendDebug(__FUNCTION__, 'Variable ' . $id . ', neuer Wert: ' . $newVariableValue . ', Helligkeit: ' . json_encode($Brightness) . '%', 0);
                        $result = @RequestAction($id, $newVariableValue);
                        if (!$result) {
                            IPS_Sleep(self::DEVICE_DELAY_MILLISECONDS);
                            $result = @RequestAction($id, $newVariableValue);
                            if (!$result) {
                                if (isset($modeText)) {
                                    $this->SendDebug(__FUNCTION__, 'Fehler, das Licht mit der ID ' . $id . ' konnte nicht ' . $modeText . ' werden!', 0);
                                    IPS_LogMessage(__FUNCTION__, 'Fehler, das Licht mit der ID ' . $id . ' konnte nicht ' . $modeText . ' werden!');
                                }
                            }
                        }
                        if (!$result) {
                            // Revert switch
                            $this->SetValue('LightMode', $actualLightMode);
                            $this->SetValue('Dimmer', $actualDimmerValue);
                            $this->SetValue('DimmingPresets', $actualDimmingPreset);
                            $this->SetValue('LastBrightness', $actualLastBrightness);
                        } else {
                            if (isset($modeText)) {
                                $this->SendDebug(__FUNCTION__, 'Das Licht wurde ' . $modeText . '.', 0);
                            }
                        }
                    }
                }
            }
        }
        return $result;
    }

    //##################### Private

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
        $this->SetValue('DutyCycleTimer', $this->GetTimeStampString($timestamp));
        $this->SendDebug(__FUNCTION__, 'Die Einschaltdauer wurde festgelegt.', 0);
    }

    /**
     * Deactivates the duty cycle timer.
     */
    private function DeactivateDutyCycleTimer(): void
    {
        $this->SendDebug(__FUNCTION__, 'Die Methode wird ausgeführt. (' . microtime(true) . ')', 0);
        $this->SetTimerInterval('SwitchLightOff', 0);
        $this->SetValue('DutyCycleTimer', '-');
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