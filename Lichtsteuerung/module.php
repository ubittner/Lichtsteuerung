<?php

/*
 * @module      Lichtsteuerung
 *
 * @prefix      LS
 *
 * @file        module.php
 *
 * @author      Ulrich Bittner
 * @copyright   (c) 2020
 * @license     CC BY-NC-SA 4.0
 *              https://creativecommons.org/licenses/by-nc-sa/4.0/
 *
 * @version     2.00-29
 * @date        2020-05-11, 18:00, 1589216400
 * @review      2020-05-11, 18:00
 *
 * @see         https://github.com/ubittner/Lichtsteuerung
 *
 * @guids       Library
 *              {46195F9D-0325-41E2-B83B-A1192293BE4E}
 *
 *              Lichtsteuerung
 *             	{14AE3EA4-76F6-4A36-BE98-628A4CB1EAC7}
 */

// Declare
declare(strict_types=1);

// Include
include_once __DIR__ . '/helper/autoload.php';

class Lichtsteuerung extends IPSModule
{
    // Helper
    use LS_backupRestore;
    use LS_checkConditions;
    use LS_isDayDetection;
    use LS_messageSink;
    use LS_presenceDetection;
    use LS_sunriseSunset;
    use LS_switchingTime;
    use LS_switchLight;
    use LS_trigger;
    use LS_twilightDetection;
    use LS_weeklySchedule;

    // Constants
    private const DEVICE_DELAY_MILLISECONDS = 250;
    private const EXECUTION_DELAY_MILLISECONDS = 100;

    /**
     * Creates this instance.
     *
     * @return bool|void
     */
    public function Create()
    {
        // Never delete this line!
        parent::Create();
        // Register properties
        $this->RegisterProperties();
        // Create profiles
        $this->CreateProfiles();
        // Register variables
        $this->RegisterVariables();
        // Register sleep mode timer
        $this->RegisterSleepModeTimer();
        // Register duty cycle timer
        $this->RegisterDutyCycleTimer();
        // Register switching timers
        $this->RegisterSwitchingTimers();
    }

    /**
     * Applies the changes of this instance.
     *
     * @return bool|void
     */
    public function ApplyChanges()
    {
        // Wait until IP-Symcon is started
        $this->RegisterMessage(0, IPS_KERNELSTARTED);
        // Never delete this line!
        parent::ApplyChanges();
        // Check runlevel
        if (IPS_GetKernelRunlevel() != KR_READY) {
            return;
        }
        // Register messages
        $this->RegisterMessages();
        // Dimming presets
        $this->UpdateDimmingPresets();
        // Create links
        $this->CreateLinks();
        // Set options
        $this->SetOptions();
        // Deactivate sleep mode
        $this->DeactivateSleepModeTimer();
        // Deactivate duty cycle timer
        $this->DeactivateDutyCycleTimer();
        // Set switching timers
        $this->SetSwitchingTimes();
        // Update light status
        $this->UpdateLightStatus();
        // Check instance status
        $this->CheckMaintenanceMode();
    }

    /**
     * Destroys this instance.
     *
     * @return bool|void
     */
    public function Destroy()
    {
        // Never delete this line!
        parent::Destroy();
        // Delete profiles
        $this->DeleteProfiles();
    }

    /**
     * Reloads the configuration form.
     */
    public function ReloadConfiguration(): void
    {
        $this->ReloadForm();
    }

    /**
     * Gets the configuration form.
     *
     * @return false|string
     */
    public function GetConfigurationForm()
    {
        $formData = json_decode(file_get_contents(__DIR__ . '/form.json'));
        // Triggers
        $triggerVariables = json_decode($this->ReadPropertyString('Triggers'));
        if (!empty($triggerVariables)) {
            foreach ($triggerVariables as $variable) {
                $rowColor = '';
                $id = $variable->ID;
                if ($id == 0 || !IPS_ObjectExists($id)) {
                    $rowColor = '#FFC0C0'; // light red
                }
                $formData->elements[9]->items[1]->values[] = ['rowColor' => $rowColor];
            }
        }
        // Registered messages
        $registeredVariables = $this->GetMessageList();
        foreach ($registeredVariables as $senderID => $messageID) {
            if (!IPS_ObjectExists($senderID)) {
                foreach ($messageID as $messageType) {
                    $this->UnregisterMessage($senderID, $messageType);
                }
                continue;
            } else {
                $senderName = IPS_GetName($senderID);
                $description = $senderName;
                $parentID = IPS_GetParent($senderID);
                if (is_int($parentID) && $parentID != 0 && @IPS_ObjectExists($parentID)) {
                    $description = IPS_GetName($parentID);
                }
            }
            switch ($messageID) {
                case [10001]:
                    $messageDescription = 'IPS_KERNELSTARTED';
                    break;

                case [10603]:
                    $messageDescription = 'VM_UPDATE';
                    break;

                case [10803]:
                    $messageDescription = 'EM_UPDATE';
                    break;

                default:
                    $messageDescription = 'keine Bezeichnung';
            }
            $formData->actions[1]->items[0]->values[] = [
                'Description'        => $description,
                'SenderID'           => $senderID,
                'SenderName'         => $senderName,
                'MessageID'          => $messageID,
                'MessageDescription' => $messageDescription];
        }
        return json_encode($formData);
    }

    /**
     * Deactivates the sleep mode timer.
     */
    public function DeactivateSleepModeTimer(): void
    {
        $this->SetValue('SleepMode', false);
        $this->SetTimerInterval('SleepMode', 0);
        $this->SetValue('SleepModeTimer', '-');
    }

    /**
     * Creates a script example.
     */
    public function CreateScriptExample(): void
    {
        $scriptID = IPS_CreateScript(0);
        IPS_SetName($scriptID, 'Beispielskript (Lichtsteuerung #' . $this->InstanceID . ')');
        $scriptContent = "<?php\n\n// Methode:\n// LS_SwitchLight(integer \$InstanceID, integer \$Brightness, integer \$DutyCycle, integer \$DutyCycleUnit);\n\n### Beispiele:\n\n// Licht ausschalten:\nLS_SwitchLight(" . $this->InstanceID . ", 0, 0, 0);\n\n// Licht für 180 Sekunden einschalten:\nLS_SwitchLight(" . $this->InstanceID . ", 100, 180, 0);\n\n// Licht für 5 Minuten einschalten:\nLS_SwitchLight(" . $this->InstanceID . ", 100, 5, 1);\n\n// Licht mit 50% Helligkeit einschalten:\nLS_SwitchLight(" . $this->InstanceID . ', 50, 0, 0);';
        IPS_SetScriptContent($scriptID, $scriptContent);
        IPS_SetParent($scriptID, $this->InstanceID);
        IPS_SetPosition($scriptID, 100);
        IPS_SetHidden($scriptID, true);
        if ($scriptID != 0) {
            echo 'Beispielskript wurde erfolgreich erstellt!';
        }
    }

    //#################### Request action

    /**
     * Requests an action via WebFront.
     *
     * @param $Ident
     * @param $Value
     * @return bool|void
     */
    public function RequestAction($Ident, $Value)
    {
        switch ($Ident) {
            case 'AutomaticMode':
                $this->ToggleAutomaticMode($Value);
                break;

            case 'SleepMode':
                $this->ToggleSleepMode($Value);
                break;

            case 'LightMode':
                $this->ExecuteLightMode($Value);
                break;

            case 'Dimmer':
                $this->SetDimmer($Value);
                break;

            case 'DimmingPresets':
                $this->ExecuteDimmingPreset($Value);
                break;

        }
    }

    /**
     * Toggles the automatic mode.
     *
     * @param bool $State
     * false    = off
     * true     = on
     */
    public function ToggleAutomaticMode(bool $State): void
    {
        $this->SendDebug(__FUNCTION__, 'Die Methode wird ausgeführt. (' . microtime(true) . ')', 0);
        $this->SetValue('AutomaticMode', $State);
    }

    /**
     * Toggles the sleep mode.
     *
     * @param bool $State
     * false    = off
     * true     = on
     */
    public function ToggleSleepMode(bool $State): void
    {
        $this->SendDebug(__FUNCTION__, 'Die Methode wird ausgeführt. (' . microtime(true) . ')', 0);
        $this->SetValue('SleepMode', $State);
        if ($State) {
            $this->SetSleepModeTimer();
        } else {
            $this->DeactivateSleepModeTimer();
        }
    }

    /**
     * Executes the light mode.
     *
     * @param int $Mode
     * 0    = off
     * 1    = timer
     * 2    = on
     */
    public function ExecuteLightMode(int $Mode): void
    {
        $this->SendDebug(__FUNCTION__, 'Die Methode wird ausgeführt. (' . microtime(true) . ')', 0);
        switch ($Mode) {
            // Off
            case 0:
                $settings = json_decode($this->ReadPropertyString('LightOff'), true);
                $action = true;
                $mode = 0;
                break;

            // Timer
            case 1:
                $settings = json_decode($this->ReadPropertyString('Timer'), true);
                $action = true;
                $mode = 1;
                break;

            // On
            case 2:
                $settings = json_decode($this->ReadPropertyString('LightOn'), true);
                $action = true;
                $mode = 3;
                break;

        }
        // Trigger action
        if (isset($action) && isset($mode) && $action) {
            if (!empty($settings)) {
                foreach ($settings as $setting) {
                    if ($setting['UseSettings']) {
                        $brightness = intval($setting['Brightness']);
                        // Check conditions
                        $checkConditions = $this->CheckAllConditions(json_encode($setting));
                        if (!$checkConditions) {
                            return;
                        }
                        $this->SetValue('LightMode', $mode);
                        if (boolval($setting['UpdateLastBrightness'])) {
                            $this->SetValue('LastBrightness', $brightness);
                        }
                        $duration = 0;
                        $durationUnit = 0;
                        if ($mode == 1) { // Timer
                            $duration = $setting['DutyCycle'];
                            $durationUnit = $setting['DutyCycleUnit'];
                        }
                        $this->SwitchLight($brightness, $duration, $durationUnit);
                    }
                }
            }
        }
    }

    /**
     * Sets the dimmer.
     *
     * @param int $Brightness
     */
    public function SetDimmer(int $Brightness): void
    {
        $this->SendDebug(__FUNCTION__, 'Die Methode wird ausgeführt. (' . microtime(true) . ')', 0);
        if ($this->ReadPropertyBoolean('DimmerUpdateLastBrightness')) {
            $this->SetValue('LastBrightness', $Brightness);
        }
        $this->SwitchLight(intval($Brightness), 0, 0);
    }

    /**
     * Executes a preset and switches the light to the brightness.
     *
     * @param int $Brightness
     */
    public function ExecuteDimmingPreset(int $Brightness): void
    {
        $this->SendDebug(__FUNCTION__, 'Die Methode wird ausgeführt. (' . microtime(true) . ')', 0);
        if ($this->ReadPropertyBoolean('DimmingPresetsUpdateLastBrightness')) {
            $this->SetValue('LastBrightness', $Brightness);
        }
        $this->SwitchLight(intval($Brightness), 0, 0);
    }

    //#################### Private

    /**
     * Applies the changes if the kernel is ready.
     */
    private function KernelReady()
    {
        $this->ApplyChanges();
    }

    /**
     * Registers the properties.
     */
    private function RegisterProperties(): void
    {
        // General options
        $this->RegisterPropertyBoolean('MaintenanceMode', false);
        $this->RegisterPropertyBoolean('EnableAutomaticMode', true);
        $this->RegisterPropertyBoolean('EnableSleepMode', true);
        $this->registerPropertyInteger('SleepDuration', 12);
        $this->RegisterPropertyBoolean('EnableLightMode', true);
        $this->RegisterPropertyString('LightOff', '[{"LabelLightOff":"","UseSettings":true,"Brightness":0,"UpdateLastBrightness":false,"LabelSwitchingConditions":"","CheckAutomaticMode":0,"CheckSleepMode":0,"CheckLightMode":0,"CheckIsDay":0,"CheckTwilight":0,"CheckPresence":0}]');
        $this->RegisterPropertyString('Timer', '[{"LabelTimer":"","UseSettings":true,"Brightness":50,"UpdateLastBrightness":false,"DutyCycle":30,"DutyCycleUnit":1,"LabelSwitchingConditions":"","CheckAutomaticMode":0,"CheckSleepMode":0,"CheckLightMode":0,"CheckIsDay":0,"CheckTwilight":0,"CheckPresence":0,"LabelOperationalAction":"","OperationalAction":0,"DefinedBrightness":0}]');
        $this->RegisterPropertyString('LightOn', '[{"LabelLightOn":"","UseSettings":true,"Brightness":100,"UpdateLastBrightness":false,"LabelSwitchingConditions":"","CheckAutomaticMode":0,"CheckSleepMode":0,"CheckLightMode":0,"CheckIsDay":0,"CheckTwilight":0,"CheckPresence":0}]');
        $this->RegisterPropertyBoolean('EnableDimmer', true);
        $this->RegisterPropertyBoolean('DimmerUpdateLastBrightness', true);
        $this->RegisterPropertyBoolean('EnableDimmingPresets', true);
        $this->RegisterPropertyBoolean('DimmingPresetsUpdateLastBrightness', true);
        $this->RegisterPropertyString('DimmingPresets', '[{"DimmingValue":0,"DimmingText":"0 %"},{"DimmingValue":25,"DimmingText":"25 %"}, {"DimmingValue":50,"DimmingText":"50 %"},{"DimmingValue":75,"DimmingText":"75 %"},{"DimmingValue":100,"DimmingText":"100 %"}]');
        $this->RegisterPropertyBoolean('EnableLastBrightness', true);
        $this->RegisterPropertyBoolean('EnableLastBrightnessManualChange', true);
        $this->RegisterPropertyBoolean('EnableSleepModeTimer', true);
        $this->RegisterPropertyBoolean('EnableDutyCycleTimer', true);
        $this->RegisterPropertyBoolean('EnableNextSwitchingTime', true);
        $this->RegisterPropertyBoolean('EnableSunrise', true);
        $this->RegisterPropertyBoolean('EnableSunset', true);
        $this->RegisterPropertyBoolean('EnableWeeklySchedule', true);
        $this->RegisterPropertyBoolean('EnableIsDay', true);
        $this->RegisterPropertyBoolean('EnableTwilight', true);
        $this->RegisterPropertyBoolean('EnablePresence', true);
        $this->RegisterPropertyBoolean('UseMessageSinkDebug', false);
        // Light
        $this->RegisterPropertyInteger('Light', 0);
        $this->RegisterPropertyBoolean('LightUpdateStatus', false);
        $this->RegisterPropertyBoolean('LightUpdateLastBrightness', false);
        // Switching times
        $this->RegisterPropertyString('SwitchingTimeOne', '{"hour":0,"minute":0,"second":0}');
        $this->RegisterPropertyString('SwitchingTimeOneActions', '[]');
        $this->RegisterPropertyString('SwitchingTimeTwo', '{"hour":0,"minute":0,"second":0}');
        $this->RegisterPropertyString('SwitchingTimeTwoActions', '[]');
        $this->RegisterPropertyString('SwitchingTimeThree', '{"hour":0,"minute":0,"second":0}');
        $this->RegisterPropertyString('SwitchingTimeThreeActions', '[]');
        $this->RegisterPropertyString('SwitchingTimeFour', '{"hour":0,"minute":0,"second":0}');
        $this->RegisterPropertyString('SwitchingTimeFourActions', '[]');
        // Sunrise and sunset
        $this->RegisterPropertyInteger('Sunrise', 0);
        $this->RegisterPropertyString('SunriseActions', '[]');
        $this->RegisterPropertyInteger('Sunset', 0);
        $this->RegisterPropertyString('SunsetActions', '[]');
        // Weekly schedule
        $this->RegisterPropertyInteger('WeeklySchedule', 0);
        $this->RegisterPropertyString('WeeklyScheduleActionOne', '[]');
        $this->RegisterPropertyString('WeeklyScheduleActionTwo', '[]');
        // Is day
        $this->RegisterPropertyInteger('IsDay', 0);
        $this->RegisterPropertyString('NightAction', '[]');
        $this->RegisterPropertyString('DayAction', '[]');
        // Twilight
        $this->RegisterPropertyInteger('TwilightStatus', 0);
        $this->RegisterPropertyString('TwilightDayAction', '[]');
        $this->RegisterPropertyString('TwilightNightAction', '[]');
        // Presence and absence
        $this->RegisterPropertyInteger('PresenceStatus', 0);
        $this->RegisterPropertyString('AbsenceAction', '[]');
        $this->RegisterPropertyString('PresenceAction', '[]');
        // Triggers
        $this->RegisterPropertyString('Triggers', '[]');
    }

    /**
     * Creates the profiles.
     */
    private function CreateProfiles(): void
    {
        // Automatic mode
        $profile = 'LS.' . $this->InstanceID . '.AutomaticMode';
        if (!IPS_VariableProfileExists($profile)) {
            IPS_CreateVariableProfile($profile, 0);
        }
        IPS_SetVariableProfileAssociation($profile, 0, 'Aus', 'Execute', -1);
        IPS_SetVariableProfileAssociation($profile, 1, 'An', 'Clock', 0x00FF00);
        // Sleep mode
        $profile = 'LS.' . $this->InstanceID . '.SleepMode';
        if (!IPS_VariableProfileExists($profile)) {
            IPS_CreateVariableProfile($profile, 0);
        }
        IPS_SetVariableProfileAssociation($profile, 0, 'Aus', 'Sleep', -1);
        IPS_SetVariableProfileAssociation($profile, 1, 'An', 'Sleep', 0x00FF00);
        // Light mode
        $profileName = 'LS.' . $this->InstanceID . '.LightMode';
        if (!IPS_VariableProfileExists($profileName)) {
            IPS_CreateVariableProfile($profileName, 1);
        }
        IPS_SetVariableProfileIcon($profile, 'Bulb');
        IPS_SetVariableProfileAssociation($profileName, 0, 'Aus', 'Bulb', 0x0000FF);
        IPS_SetVariableProfileAssociation($profileName, 1, 'Timer', 'Bulb', 0xFFFF00);
        IPS_SetVariableProfileAssociation($profileName, 2, 'An', 'Bulb', 0x00FF00);
        // Dimming presets
        $profile = 'LS.' . $this->InstanceID . '.DimmingPresets';
        if (!IPS_VariableProfileExists($profile)) {
            IPS_CreateVariableProfile($profile, 1);
        }
        IPS_SetVariableProfileIcon($profile, 'Menu');
    }

    /**
     * Updates the dimming presets.
     */
    private function UpdateDimmingPresets(): void
    {
        // Dimming presets
        $profile = 'LS.' . $this->InstanceID . '.DimmingPresets';
        $associations = IPS_GetVariableProfile($profile)['Associations'];
        if (!empty($associations)) {
            foreach ($associations as $association) {
                // Delete
                IPS_SetVariableProfileAssociation($profile, $association['Value'], '', '', -1);
            }
        }
        $dimmingPresets = json_decode($this->ReadPropertyString('DimmingPresets'));
        if (!empty($dimmingPresets)) {
            foreach ($dimmingPresets as $preset) {
                // Create
                IPS_SetVariableProfileAssociation($profile, $preset->DimmingValue, $preset->DimmingText, '', -1);
            }
        }
    }

    /**
     * Sets the dimming preset to the closest value.
     *
     * @param int $Brightness
     */
    private function SetClosestDimmingPreset(int $Brightness): void
    {
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
    }

    /**
     * Deletes the custom profiles of this instance.
     */
    private function DeleteProfiles(): void
    {
        $profiles = ['AutomaticMode', 'SleepMode', 'LightMode', 'DimmingPresets'];
        foreach ($profiles as $profile) {
            $profileName = 'LS.' . $this->InstanceID . '.' . $profile;
            if (@IPS_VariableProfileExists($profileName)) {
                IPS_DeleteVariableProfile($profileName);
            }
        }
    }

    /**
     * Registers the variables.
     */
    private function RegisterVariables(): void
    {
        // Automatic mode
        $profile = 'LS.' . $this->InstanceID . '.AutomaticMode';
        $this->RegisterVariableBoolean('AutomaticMode', 'Automatik', $profile, 0);
        $this->EnableAction('AutomaticMode');
        // Sleep mode
        $profile = 'LS.' . $this->InstanceID . '.SleepMode';
        $this->RegisterVariableBoolean('SleepMode', 'Ruhe-Modus', $profile, 1);
        $this->EnableAction('SleepMode');
        // Light mode
        $profile = 'LS.' . $this->InstanceID . '.LightMode';
        $this->RegisterVariableInteger('LightMode', 'Licht', $profile, 2);
        $this->EnableAction('LightMode');
        // Dimmer
        $profile = '~Intensity.100';
        $this->RegisterVariableInteger('Dimmer', 'Lichthelligkeit', $profile, 3);
        $this->EnableAction('Dimmer');
        // Dimming presets
        $profile = 'LS.' . $this->InstanceID . '.DimmingPresets';
        $this->RegisterVariableInteger('DimmingPresets', 'Helligkeit Voreinstellungen', $profile, 4);
        $this->EnableAction('DimmingPresets');
        // Last brightness
        $profile = '~Intensity.100';
        $this->RegisterVariableInteger('LastBrightness', 'Letzte Helligkeit', $profile, 5);
        IPS_SetIcon($this->GetIDForIdent('LastBrightness'), 'Information');
        // Sleep mode timer
        $this->RegisterVariableString('SleepModeTimer', 'Ruhe-Modus Timer', '', 6);
        IPS_SetIcon($this->GetIDForIdent('SleepModeTimer'), 'Clock');
        // Light mode timer
        $this->RegisterVariableString('DutyCycleTimer', 'Einschaltdauer bis', '', 7);
        $id = $this->GetIDForIdent('DutyCycleTimer');
        IPS_SetIcon($id, 'Clock');
        // Next switching time
        $this->RegisterVariableString('NextSwitchingTime', 'Nächste Schaltzeit', '', 8);
        IPS_SetIcon($this->GetIDForIdent('NextSwitchingTime'), 'Information');
    }

    /**
     * Creates links.
     */
    private function CreateLinks(): void
    {
        // Sunrise
        $targetID = 0;
        $sunrise = $this->ReadPropertyInteger('Sunrise');
        if ($sunrise != 0 && @IPS_ObjectExists($sunrise)) {
            $targetID = $sunrise;
        }
        $linkID = @IPS_GetLinkIDByName('Nächster Sonnenaufgang', $this->InstanceID);
        if ($targetID != 0 && @IPS_ObjectExists($targetID)) {
            // Check for existing link
            if ($linkID === false) {
                $linkID = IPS_CreateLink();
            }
            IPS_SetParent($linkID, $this->InstanceID);
            IPS_SetPosition($linkID, 9);
            IPS_SetName($linkID, 'Nächster Sonnenaufgang');
            IPS_SetIcon($linkID, 'Sun');
            IPS_SetLinkTargetID($linkID, $targetID);
        } else {
            if ($linkID !== false) {
                IPS_SetHidden($linkID, true);
            }
        }
        // Sunset
        $targetID = 0;
        $sunset = $this->ReadPropertyInteger('Sunset');
        if ($sunset != 0 && @IPS_ObjectExists($sunset)) {
            $targetID = $sunset;
        }
        $linkID = @IPS_GetLinkIDByName('Nächster Sonnenuntergang', $this->InstanceID);
        if ($targetID != 0 && @IPS_ObjectExists($targetID)) {
            // Check for existing link
            if ($linkID === false) {
                $linkID = IPS_CreateLink();
            }
            IPS_SetParent($linkID, $this->InstanceID);
            IPS_SetPosition($linkID, 10);
            IPS_SetName($linkID, 'Nächster Sonnenuntergang');
            IPS_SetIcon($linkID, 'Moon');
            IPS_SetLinkTargetID($linkID, $targetID);
        } else {
            if ($linkID !== false) {
                IPS_SetHidden($linkID, true);
            }
        }
        // Weekly schedule
        $targetID = $this->ReadPropertyInteger('WeeklySchedule');
        $linkID = @IPS_GetLinkIDByName('Nächstes Wochenplanereignis', $this->InstanceID);
        if ($targetID != 0 && @IPS_ObjectExists($targetID)) {
            // Check for existing link
            if ($linkID === false) {
                $linkID = IPS_CreateLink();
            }
            IPS_SetParent($linkID, $this->InstanceID);
            IPS_SetPosition($linkID, 11);
            IPS_SetName($linkID, 'Nächstes Wochenplanereignis');
            IPS_SetIcon($linkID, 'Calendar');
            IPS_SetLinkTargetID($linkID, $targetID);
        } else {
            if ($linkID !== false) {
                IPS_SetHidden($linkID, true);
            }
        }
        // Is day
        $targetID = $this->ReadPropertyInteger('IsDay');
        $linkID = @IPS_GetLinkIDByName('Ist es Tag', $this->InstanceID);
        if ($targetID != 0 && @IPS_ObjectExists($targetID)) {
            // Check for existing link
            if ($linkID === false) {
                $linkID = IPS_CreateLink();
            }
            IPS_SetParent($linkID, $this->InstanceID);
            IPS_SetPosition($linkID, 12);
            IPS_SetName($linkID, 'Ist es Tag');
            IPS_SetLinkTargetID($linkID, $targetID);
        } else {
            if ($linkID !== false) {
                IPS_SetHidden($linkID, true);
            }
        }
        // Twilight
        $targetID = $this->ReadPropertyInteger('TwilightStatus');
        $linkID = @IPS_GetLinkIDByName('Dämmerungsstatus', $this->InstanceID);
        if ($targetID != 0 && @IPS_ObjectExists($targetID)) {
            // Check for existing link
            if ($linkID === false) {
                $linkID = IPS_CreateLink();
            }
            IPS_SetParent($linkID, $this->InstanceID);
            IPS_SetPosition($linkID, 13);
            IPS_SetName($linkID, 'Dämmerungsstatus');
            IPS_SetLinkTargetID($linkID, $targetID);
        } else {
            if ($linkID !== false) {
                IPS_SetHidden($linkID, true);
            }
        }
        // Presence
        $targetID = $this->ReadPropertyInteger('PresenceStatus');
        $linkID = @IPS_GetLinkIDByName('Anwesenheitsstatus', $this->InstanceID);
        if ($targetID != 0 && @IPS_ObjectExists($targetID)) {
            // Check for existing link
            if ($linkID === false) {
                $linkID = IPS_CreateLink();
            }
            IPS_SetParent($linkID, $this->InstanceID);
            IPS_SetPosition($linkID, 14);
            IPS_SetName($linkID, 'Anwesenheitsstatus');
            IPS_SetLinkTargetID($linkID, $targetID);
        } else {
            if ($linkID !== false) {
                IPS_SetHidden($linkID, true);
            }
        }
    }

    /**
     * Sets the options.
     */
    private function SetOptions(): void
    {
        // Automatic mode
        IPS_SetHidden($this->GetIDForIdent('AutomaticMode'), !$this->ReadPropertyBoolean('EnableAutomaticMode'));
        // Sleep mode
        IPS_SetHidden($this->GetIDForIdent('SleepMode'), !$this->ReadPropertyBoolean('EnableSleepMode'));
        // Light mode
        IPS_SetHidden($this->GetIDForIdent('LightMode'), !$this->ReadPropertyBoolean('EnableLightMode'));
        // Dimmer
        IPS_SetHidden($this->GetIDForIdent('Dimmer'), !$this->ReadPropertyBoolean('EnableDimmer'));
        // Dimming Presets
        IPS_SetHidden($this->GetIDForIdent('DimmingPresets'), !$this->ReadPropertyBoolean('EnableDimmingPresets'));
        // Last brightness
        IPS_SetHidden($this->GetIDForIdent('LastBrightness'), !$this->ReadPropertyBoolean('EnableLastBrightness'));
        $manualChange = $this->ReadPropertyBoolean('EnableLastBrightnessManualChange');
        if (!$manualChange) {
            $this->DisableAction('LastBrightness');
        } else {
            $this->EnableAction('LastBrightness');
        }
        // Sleep mode timer
        IPS_SetHidden($this->GetIDForIdent('SleepModeTimer'), !$this->ReadPropertyBoolean('EnableSleepModeTimer'));
        // Light mode timer
        IPS_SetHidden($this->GetIDForIdent('DutyCycleTimer'), !$this->ReadPropertyBoolean('EnableDutyCycleTimer'));
        // Next switching time
        $hide = !$this->ReadPropertyBoolean('EnableNextSwitchingTime');
        if (!$hide) {
            $properties = ['SwitchingTimeOneActions', 'SwitchingTimeTwoActions', 'SwitchingTimeThreeActions', 'SwitchingTimeFourActions'];
            $hide = true;
            foreach ($properties as $property) {
                $actions = json_decode($this->ReadPropertyString($property), true);
                if (!empty($actions)) {
                    foreach ($actions as $action) {
                        $use = $action['UseSettings'];
                        if ($use) {
                            $hide = false;
                        }
                    }
                }
            }
        }
        IPS_SetHidden($this->GetIDForIdent('NextSwitchingTime'), $hide);
        // Sunrise
        $id = @IPS_GetLinkIDByName('Nächster Sonnenaufgang', $this->InstanceID);
        if ($id !== false) {
            $hide = true;
            $sunrise = false;
            $sunriseActions = json_decode($this->ReadPropertyString('SunriseActions'), true);
            if (!empty($sunriseActions)) {
                foreach ($sunriseActions as $sunriseAction) {
                    if ($sunriseAction['UseSettings']) {
                        $sunrise = true;
                    }
                }
            }
            if ($sunrise) {
                $sunriseID = $this->ReadPropertyInteger('Sunrise');
                if ($sunriseID != 0 && @IPS_ObjectExists($sunriseID)) {
                    $hide = !$this->ReadPropertyBoolean('EnableSunrise');
                }
            }
            IPS_SetHidden($id, $hide);
        }
        // Sunset
        $id = @IPS_GetLinkIDByName('Nächster Sonnenuntergang', $this->InstanceID);
        if ($id !== false) {
            $hide = true;
            $sunset = false;
            $sunsetActions = json_decode($this->ReadPropertyString('SunriseActions'), true);
            if (!empty($sunsetActions)) {
                foreach ($sunsetActions as $sunsetAction) {
                    if ($sunsetAction['UseSettings']) {
                        $sunset = true;
                    }
                }
            }
            if ($sunset) {
                $sunsetID = $this->ReadPropertyInteger('Sunrise');
                if ($sunsetID != 0 && @IPS_ObjectExists($sunsetID)) {
                    $hide = !$this->ReadPropertyBoolean('EnableSunset');
                }
            }
            IPS_SetHidden($id, $hide);
        }
        // Weekly schedule
        $id = @IPS_GetLinkIDByName('Nächstes Wochenplanereignis', $this->InstanceID);
        if ($id !== false) {
            $hide = true;
            if ($this->ReadPropertyBoolean('EnableWeeklySchedule')) {
                if ($this->ValidateWeeklySchedule()) {
                    $hide = false;
                }
            }
            IPS_SetHidden($id, $hide);
        }
        // Is day
        $id = @IPS_GetLinkIDByName('Ist es Tag', $this->InstanceID);
        if ($id !== false) {
            $hide = true;
            $targetID = $this->ReadPropertyInteger('IsDay');
            if ($targetID != 0 && @IPS_ObjectExists($targetID)) {
                $profile = 'Location.' . $targetID . '.IsDay';
                if (!IPS_VariableProfileExists($profile)) {
                    IPS_CreateVariableProfile($profile, 0);
                    IPS_SetVariableProfileAssociation($profile, 0, 'Es ist Nacht', 'Moon', 0x0000FF);
                    IPS_SetVariableProfileAssociation($profile, 1, 'Es ist Tag', 'Sun', 0xFFFF00);
                    IPS_SetVariableCustomProfile($targetID, $profile);
                }
                if ($this->ReadPropertyBoolean('EnableIsDay')) {
                    $hide = false;
                }
            }
            IPS_SetHidden($id, $hide);
        }
        // Twilight
        $id = @IPS_GetLinkIDByName('Dämmerungsstatus', $this->InstanceID);
        if ($id !== false) {
            $hide = true;
            $targetID = $this->ReadPropertyInteger('TwilightStatus');
            if ($targetID != 0 && @IPS_ObjectExists($targetID)) {
                if ($this->ReadPropertyBoolean('EnableTwilight')) {
                    $hide = false;
                }
            }
            IPS_SetHidden($id, $hide);
        }
        // Presence
        $id = @IPS_GetLinkIDByName('Anwesenheitsstatus', $this->InstanceID);
        if ($id !== false) {
            $hide = true;
            $targetID = $this->ReadPropertyInteger('PresenceStatus');
            if ($targetID != 0 && @IPS_ObjectExists($targetID)) {
                if ($this->ReadPropertyBoolean('EnablePresence')) {
                    $hide = false;
                }
            }
            IPS_SetHidden($id, $hide);
        }
    }

    /**
     * Registers the sleep mode timer.
     */
    private function RegisterSleepModeTimer(): void
    {
        $this->RegisterTimer('SleepMode', 0, 'LS_DeactivateSleepModeTimer(' . $this->InstanceID . ');');
    }

    /**
     * Sets the sleep mode timer.
     */
    private function SetSleepModeTimer(): void
    {
        $this->SetValue('SleepMode', true);
        // Duration from hours to seconds
        $duration = $this->ReadPropertyInteger('SleepDuration') * 60 * 60;
        // Set timer interval
        $this->SetTimerInterval('SleepMode', $duration * 1000);
        $timestamp = time() + $duration;
        $this->SetValue('SleepModeTimer', date('d.m.Y, H:i:s', ($timestamp)));
    }

    /**
     * Checks for maintenance mode.
     *
     * @return bool
     * false    = normal mode
     * true     = maintenance mode
     */
    private function CheckMaintenanceMode(): bool
    {
        $result = false;
        $status = 102;
        if ($this->ReadPropertyBoolean('MaintenanceMode')) {
            $result = true;
            $status = 104;
            $this->SendDebug(__FUNCTION__, 'Abbruch, der Wartungsmodus ist aktiv!', 0);
            $this->LogMessage('ID ' . $this->InstanceID . ', ' . __FUNCTION__ . ', Abbruch, der Wartungsmodus ist aktiv!', KL_WARNING);
        }
        $this->SetStatus($status);
        IPS_SetDisabled($this->InstanceID, $result);
        return $result;
    }

    /**
     * Checks for a activated action.
     *
     * @param string $PropertyVariableName
     * @param string $PropertyActionName
     * @return bool
     * false    = no activated action available
     * true     = activate action
     */
    private function CheckAction(string $PropertyVariableName, string $PropertyActionName): bool
    {
        $result = false;
        $actions = json_decode($this->ReadPropertyString($PropertyActionName), true);
        if (!empty($actions)) {
            foreach ($actions as $action) {
                if ($action['UseSettings']) {
                    $result = true;
                }
            }
        }
        if ($result) {
            $id = $this->ReadPropertyInteger($PropertyVariableName);
            if ($id == 0 || !@IPS_ObjectExists($id)) {
                $result = false;
            }
        }
        return $result;
    }

    /**
     * Gets a string from timestamp.
     *
     * @param int $Timestamp
     * @return string
     */
    private function GetTimeStampString(int $Timestamp): string
    {
        $day = date('j', ($Timestamp));
        $month = date('F', ($Timestamp));
        switch ($month) {
            case 'January':
                $month = 'Januar';
                break;

            case 'February':
                $month = 'Februar';
                break;

            case 'March':
                $month = 'März';
                break;

            case 'April':
                $month = 'April';
                break;

            case 'May':
                $month = 'Mai';
                break;

            case 'June':
                $month = 'Juni';
                break;

            case 'July':
                $month = 'Juli';
                break;

            case 'August':
                $month = 'August';
                break;

            case 'September':
                $month = 'September';
                break;

            case 'October':
                $month = 'Oktober';
                break;

            case 'November':
                $month = 'November';
                break;

            case 'December':
                $month = 'Dezember';
                break;

        }
        $year = date('Y', ($Timestamp));
        $time = date('H:i:s', ($Timestamp));
        return $day . '. ' . $month . ' ' . $year . ' ' . $time;
    }
}