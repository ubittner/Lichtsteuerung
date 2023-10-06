<?php

/** @noinspection PhpVoidFunctionResultUsedInspection */

/**
 * @project       Lichtsteuerung/helper
 * @file          LS_SwitchLight.php
 * @author        Ulrich Bittner
 * @copyright     2023 Ulrich Bittner
 * @license       https://creativecommons.org/licenses/by-nc-sa/4.0/ CC BY-NC-SA 4.0
 */

declare(strict_types=1);

trait LS_SwitchLight
{
    /**
     * Updates the light status
     *
     * @return void
     * @throws Exception
     */
    public function UpdateLightStatus(): void
    {
        $this->SendDebug(__FUNCTION__, 'wird ausgeführt', 0);
        $id = $this->ReadPropertyInteger('Light');
        if ($id <= 1 || @!IPS_ObjectExists($id)) {
            $this->SendDebug(__FUNCTION__, 'Abbruch, es ist kein Licht zum Schalten vorhanden!', 0);
            return;
        }
        //Enter semaphore
        if (!$this->LockSemaphore('SwitchLight')) {
            $this->SendDebug(__FUNCTION__, 'Abort, Semaphore reached!', 0);
            $this->UnlockSemaphore('SwitchLight');
            return;
        }
        $variableType = @IPS_GetVariable($id)['VariableType'];
        switch ($variableType) {
            case 0: //Boolean
                $actualValue = boolval(GetValue($id));
                //Off
                $mode = 0;
                $brightness = 0;
                if ($actualValue) {
                    //On
                    $mode = 2;
                    $brightness = 100;
                }
                break;

            case 1: //Integer
                $actualValue = intval(GetValue($id));
                //Off
                $mode = 0;
                $brightness = 0;
                if ($actualValue > 0) {
                    //On
                    $mode = 2;
                    $brightness = $actualValue;
                }
                break;

            case 2: //Float
                $actualValue = floatval(GetValue($id));
                //Off
                $mode = 0;
                $brightness = 0;
                if ($actualValue > 0) {
                    //On
                    $mode = 2;
                    $brightness = intval($actualValue * 100);
                }
                break;

            default:
                $this->SendDebug(__FUNCTION__, 'Abbruch, der Variablentyp wird nicht unterstützt!', 0);
        }
        if (isset($mode) && isset($brightness)) {
            $this->SendDebug(__FUNCTION__, 'Modus: ' . $mode . ', Helligkeit: ' . $brightness . '%.', 0);
            $actualLightMode = $this->GetValue('LightMode');
            //Light mode is off or on
            if ($actualLightMode != 1) {
                $this->SetValue('LightMode', $mode);
            }
            //Light mode is timer and light is off
            if ($actualLightMode == 1 && $mode == 0) {
                $this->SetValue('LightMode', $mode);
                $this->DeactivateDutyCycleTimer();
            }
            //Dimmer
            $this->SetValue('Dimmer', $brightness);
            $this->SetClosestDimmingPreset($brightness);
            //Last brightness
            if ($this->ReadPropertyBoolean('LightStatusUpdateLastBrightness')) {
                $this->SetValue('LastBrightness', $brightness);
            }
        }
        //Leave semaphore
        $this->UnlockSemaphore('SwitchLight');
    }

    /**
     * Deactivates the sleep mode timer.
     *
     * @return void
     * @throws Exception
     */
    public function DeactivateSleepModeTimer(): void
    {
        $this->SetValue('SleepMode', false);
        $this->SetTimerInterval('SleepMode', 0);
        $this->SetValue('SleepModeTimer', '-');
    }

    /**
     * Switches the light to the brightness value.
     *
     * @param int $Brightness
     * @param int $DutyCycle
     * @param int $DutyCycleUnit
     * 0 =  seconds,
     * 1 =  minutes
     *
     * @return bool
     * false =  mismatch,
     * true =   condition is valid
     *
     * @throws Exception
     */
    public function SwitchLight(int $Brightness, int $DutyCycle = 0, int $DutyCycleUnit = 0): bool
    {
        $this->SendDebug(__FUNCTION__, 'wird ausgeführt', 0);
        $id = $this->ReadPropertyInteger('Light');
        if ($id == 0 || !@IPS_ObjectExists($id)) {
            $this->SendDebug(__FUNCTION__, 'Abbruch, es ist kein Licht vorhanden!', 0);
            return false;
        }
        if ($this->CheckMaintenance()) {
            return false;
        }
        $result = false;
        $actualLightMode = intval($this->GetValue('LightMode'));
        $actualDimmerValue = intval($this->GetValue('Dimmer'));
        $this->SendDebug(__FUNCTION__, 'Aktueller Helligkeitswert: ' . $actualDimmerValue, 0);
        $actualDimmingPreset = intval($this->GetValue('DimmingPresets'));
        $actualLastBrightness = intval($this->GetValue('LastBrightness'));
        //Off
        if ($Brightness == 0) {
            $mode = 0;
            $modeText = 'ausgeschaltet (Aus)';
            $this->DeactivateDutyCycleTimer();
        }
        //Timer
        if ($Brightness > 0 && $DutyCycle != 0) {
            $mode = 1;
            $modeText = 'eingeschaltet (Timer)';
            $this->SetDutyCycleTimer($DutyCycle, $DutyCycleUnit);
        }
        //On
        if ($Brightness > 0 && $DutyCycle == 0) {
            $mode = 2;
            $modeText = 'eingeschaltet (An)';
            if ($actualLightMode == 1) {
                $this->DeactivateDutyCycleTimer();
            }
        }
        if ($DutyCycle == 0) {
            $this->DeactivateDutyCycleTimer();
        }
        if (isset($modeText)) {
            $this->SendDebug(__FUNCTION__, 'Licht wird ' . $modeText, 0);
        }
        if (isset($mode)) {
            $this->SetValue('LightMode', $mode);
            $this->SetValue('Dimmer', $Brightness);
            $this->SendDebug(__FUNCTION__, 'Neuer Helligkeitswert: ' . $Brightness, 0);
            $this->SetClosestDimmingPreset($Brightness);
            $variableType = @IPS_GetVariable($id)['VariableType'];
            switch ($variableType) {
                case 0: //Boolean
                    $newVariableValue = boolval($Brightness);
                    //Command control value
                    if ($Brightness == 0) {
                        $newVariableValueCommandControl = 'false';
                    } else {
                        $newVariableValueCommandControl = 'true';
                    }
                    break;

                case 1:  //Integer
                    $newVariableValue = $Brightness;
                    //Command control value
                    $newVariableValueCommandControl = $Brightness;
                    break;

                case 2: //Float
                    $newVariableValue = floatval($Brightness / 100);
                    //Command control value
                    $newVariableValueCommandControl = floatval($Brightness / 100);
                    break;
            }
            if (isset($newVariableValue) && isset($newVariableValueCommandControl)) {
                if ($this->ReadPropertyBoolean('SwitchChangesOnly')) {
                    if ($actualDimmerValue == $Brightness) {
                        $this->SendDebug(__FUNCTION__, 'Es wird bereits die gleiche Helligkeit verwendet!', 0);
                        return true;
                    }
                }
                //Enter semaphore
                if (!$this->LockSemaphore('SwitchLight')) {
                    $this->SendDebug(__FUNCTION__, 'Abort, Semaphore reached!', 0);
                    $this->UnlockSemaphore('SwitchLight');
                    return false;
                }
                //Command control
                $this->SendDebug(__FUNCTION__, 'Neuer Helligkeitswert (Ablaufsteuerung): ' . $newVariableValueCommandControl, 0);
                $commandControl = $this->ReadPropertyInteger('CommandControl');
                if ($commandControl > 1 && @IPS_ObjectExists($commandControl)) {
                    $commands = [];
                    $commands[] = '@RequestAction(' . $id . ', ' . $newVariableValueCommandControl . ');';
                    $this->SendDebug(__FUNCTION__, 'Befehl: ' . json_encode(json_encode($commands)), 0);
                    $scriptText = self::ABLAUFSTEUERUNG_MODULE_PREFIX . '_ExecuteCommands(' . $commandControl . ', ' . json_encode(json_encode($commands)) . ');';
                    $this->SendDebug(__FUNCTION__, 'Ablaufsteuerung: ' . $scriptText, 0);
                    $result = @IPS_RunScriptText($scriptText);
                } else {
                    $this->SendDebug(__FUNCTION__, 'Neuer Helligkeitswert: ' . $newVariableValue, 0);
                    $result = @RequestAction($id, $newVariableValue);
                    if (!$result) {
                        //Retry
                        IPS_Sleep(250);
                        $result = @RequestAction($id, $newVariableValue);
                    }
                }
                if (!$result) {
                    if (isset($modeText)) {
                        $this->SendDebug(__FUNCTION__, 'Fehler, das Licht mit der ID ' . $id . ' konnte nicht ' . $modeText . ' werden!', 0);
                        $this->LogMessage(__FUNCTION__, 'Fehler, das Licht mit der ID ' . $id . ' konnte nicht ' . $modeText . ' werden!');
                    }
                    //Revert
                    $this->SetValue('LightMode', $actualLightMode);
                    $this->SetValue('Dimmer', $actualDimmerValue);
                    $this->SetValue('DimmingPresets', $actualDimmingPreset);
                    $this->SetValue('LastBrightness', $actualLastBrightness);
                } else {
                    if (isset($modeText)) {
                        $this->SendDebug(__FUNCTION__, 'Das Licht wurde ' . $modeText . '.', 0);
                    }
                }
                //Leave semaphore
                $this->UnlockSemaphore('SwitchLight');
            }
        }
        return $result;
    }

    /**
     * Triggers an execution delay.
     *
     * @param int $Delay
     * @return void
     */
    protected function TriggerExecutionDelay(int $Delay): void
    {
        if ($Delay != 0) {
            $this->SendDebug(__FUNCTION__, 'Die Verzögerung von ' . $Delay . ' Sekunden wird ausgeführt.', 0);
            IPS_Sleep($Delay * 1000);
        }
    }

    #################### Private

    /**
     * Sets the dimming preset to the closest value.
     *
     * @param int $Brightness
     */
    private function SetClosestDimmingPreset(int $Brightness): void
    {
        $profile = self::MODULE_PREFIX . '.' . $this->InstanceID . '.DimmingPresets';
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
    }

    /**
     * Sets the interval for the duty cycle timer.
     *
     * @param int $DutyCycle
     * @param int $DutyCycleUnit
     * 0 =  seconds,
     * 1 =  minutes
     *
     * @return void
     * @throws Exception
     */
    private function SetDutyCycleTimer(int $DutyCycle, int $DutyCycleUnit): void
    {
        $this->SendDebug(__FUNCTION__, 'wird ausgeführt', 0);
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
     *
     * @return void
     * @throws Exception
     */
    private function DeactivateDutyCycleTimer(): void
    {
        $this->SendDebug(__FUNCTION__, 'wird ausgeführt', 0);
        $this->SetTimerInterval('SwitchLightOff', 0);
        $this->SetValue('DutyCycleTimer', '-');
        $this->SendDebug(__FUNCTION__, 'Der Timer wurde deaktiviert.', 0);
    }
}