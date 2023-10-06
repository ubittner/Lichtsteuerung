<?php

/** @noinspection PhpUnused */

/**
 * @project       Lichtsteuerung
 * @file          module.php
 * @author        Ulrich Bittner
 * @copyright     2023 Ulrich Bittner
 * @license       https://creativecommons.org/licenses/by-nc-sa/4.0/ CC BY-NC-SA 4.0
 */

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

include_once __DIR__ . '/helper/LS_autoload.php';

class Lichtsteuerung extends IPSModule
{
    //Helper
    use LS_CheckConditions;
    use LS_ConfigurationForm;
    use LS_IsDayDetection;
    use LS_PresenceDetection;
    use LS_SunriseSunset;
    use LS_SwitchingTime;
    use LS_SwitchLight;
    use LS_Trigger;
    use LS_TwilightDetection;
    use LS_WeeklySchedule;

    //Constants
    private const LIBRARY_GUID = '{46195F9D-0325-41E2-B83B-A1192293BE4E}';
    private const MODULE_GUID = '{14AE3EA4-76F6-4A36-BE98-628A4CB1EAC7}';
    private const MODULE_NAME = 'Lichtsteuerung';
    private const MODULE_PREFIX = 'LS';
    private const ABLAUFSTEUERUNG_MODULE_GUID = '{0559B287-1052-A73E-B834-EBD9B62CB938}';
    private const ABLAUFSTEUERUNG_MODULE_PREFIX = 'AST';

    public function Create()
    {
        //Never delete this line!
        parent::Create();

        ########## Properties

        //Info
        $this->RegisterPropertyString('Note', '');

        //Light
        $this->RegisterPropertyInteger('Light', 0);
        $this->RegisterPropertyBoolean('SwitchChangesOnly', true);
        $this->RegisterPropertyInteger('SleepDuration', 12);
        $this->RegisterPropertyString('LightOff', '[{"LabelLightOff":"","UseSettings":true,"Brightness":0,"UpdateLastBrightness":false,"LabelSwitchingConditions":"","CheckAutomaticMode":0,"CheckSleepMode":0,"CheckLightMode":0,"CheckIsDay":0,"CheckTwilight":0,"CheckPresence":0}]');
        $this->RegisterPropertyString('Timer', '[{"LabelTimer":"","UseSettings":true,"Brightness":50,"UpdateLastBrightness":false,"DutyCycle":30,"DutyCycleUnit":1,"LabelSwitchingConditions":"","CheckAutomaticMode":0,"CheckSleepMode":0,"CheckLightMode":0,"CheckIsDay":0,"CheckTwilight":0,"CheckPresence":0,"LabelOperationalAction":"","OperationalAction":0,"DefinedBrightness":0}]');
        $this->RegisterPropertyString('LightOn', '[{"LabelLightOn":"","UseSettings":true,"Brightness":100,"UpdateLastBrightness":false,"LabelSwitchingConditions":"","CheckAutomaticMode":0,"CheckSleepMode":0,"CheckLightMode":0,"CheckIsDay":0,"CheckTwilight":0,"CheckPresence":0}]');
        $this->RegisterPropertyBoolean('DimmerUpdateLastBrightness', true);
        $this->RegisterPropertyString('DimmingPresets', '[{"DimmingValue":0,"DimmingText":"0 %"},{"DimmingValue":25,"DimmingText":"25 %"}, {"DimmingValue":50,"DimmingText":"50 %"},{"DimmingValue":75,"DimmingText":"75 %"},{"DimmingValue":100,"DimmingText":"100 %"}]');
        $this->RegisterPropertyBoolean('DimmingPresetsUpdateLastBrightness', true);
        $this->RegisterPropertyBoolean('EnableLastBrightnessManualChange', true);

        //Light status
        $this->RegisterPropertyBoolean('UseImmediateLightStatusUpdate', false);
        $this->RegisterPropertyInteger('LightStatusUpdateInterval', 0);
        $this->RegisterPropertyBoolean('LightStatusUpdateLastBrightness', false);

        //Switching times
        $this->RegisterPropertyString('SwitchingTimeOne', '{"hour":0,"minute":0,"second":0}');
        $this->RegisterPropertyString('SwitchingTimeOneActions', '[]');
        $this->RegisterPropertyString('SwitchingTimeTwo', '{"hour":0,"minute":0,"second":0}');
        $this->RegisterPropertyString('SwitchingTimeTwoActions', '[]');
        $this->RegisterPropertyString('SwitchingTimeThree', '{"hour":0,"minute":0,"second":0}');
        $this->RegisterPropertyString('SwitchingTimeThreeActions', '[]');
        $this->RegisterPropertyString('SwitchingTimeFour', '{"hour":0,"minute":0,"second":0}');
        $this->RegisterPropertyString('SwitchingTimeFourActions', '[]');

        //Sunrise
        $this->RegisterPropertyInteger('Sunrise', 0);
        $this->RegisterPropertyString('SunriseActions', '[]');

        //Sunset
        $this->RegisterPropertyInteger('Sunset', 0);
        $this->RegisterPropertyString('SunsetActions', '[]');

        //Weekly schedule
        $this->RegisterPropertyInteger('WeeklySchedule', 0);
        $this->RegisterPropertyString('WeeklyScheduleActionOne', '[]');
        $this->RegisterPropertyString('WeeklyScheduleActionTwo', '[]');

        //Is day
        $this->RegisterPropertyInteger('IsDay', 0);
        $this->RegisterPropertyString('DayAction', '[]');
        $this->RegisterPropertyString('NightAction', '[]');

        //Twilight
        $this->RegisterPropertyInteger('Twilight', 0);
        $this->RegisterPropertyString('TwilightDayAction', '[]');
        $this->RegisterPropertyString('TwilightNightAction', '[]');

        //Presence
        $this->RegisterPropertyInteger('Presence', 0);
        $this->RegisterPropertyString('PresenceAction', '[]');
        $this->RegisterPropertyString('AbsenceAction', '[]');

        //Triggers
        $this->RegisterPropertyString('Triggers', '[]');

        //Command control
        $this->RegisterPropertyInteger('CommandControl', 0);

        //Visualisation
        $this->RegisterPropertyBoolean('EnableActive', false);
        $this->RegisterPropertyBoolean('EnableAutomaticMode', true);
        $this->RegisterPropertyBoolean('EnableSleepMode', true);
        $this->RegisterPropertyBoolean('EnableLightMode', true);
        $this->RegisterPropertyBoolean('EnableDimmer', true);
        $this->RegisterPropertyBoolean('EnableDimmingPresets', true);
        $this->RegisterPropertyBoolean('EnableLastBrightness', true);
        $this->RegisterPropertyBoolean('EnableSleepModeTimer', true);
        $this->RegisterPropertyBoolean('EnableDutyCycleTimer', true);
        $this->RegisterPropertyBoolean('EnableNextSwitchingTime', true);
        $this->RegisterPropertyBoolean('EnableSunrise', true);
        $this->RegisterPropertyBoolean('EnableSunset', true);
        $this->RegisterPropertyBoolean('EnableWeeklySchedule', true);
        $this->RegisterPropertyBoolean('EnableIsDay', true);
        $this->RegisterPropertyBoolean('EnableTwilight', true);
        $this->RegisterPropertyBoolean('EnablePresence', true);

        ########## Variables

        //Active
        $id = @$this->GetIDForIdent('Active');
        $this->RegisterVariableBoolean('Active', 'Aktiv', '~Switch', 10);
        $this->EnableAction('Active');
        if (!$id) {
            $this->SetValue('Active', true);
        }

        //Automatic mode
        $profile = self::MODULE_PREFIX . '.' . $this->InstanceID . '.AutomaticMode';
        if (!IPS_VariableProfileExists($profile)) {
            IPS_CreateVariableProfile($profile, 0);
        }
        IPS_SetVariableProfileAssociation($profile, 0, 'Aus', 'Execute', -1);
        IPS_SetVariableProfileAssociation($profile, 1, 'An', 'Clock', 0x00FF00);
        $this->RegisterVariableBoolean('AutomaticMode', 'Automatik', $profile, 20);
        $this->EnableAction('AutomaticMode');

        //Sleep mode
        $profile = self::MODULE_PREFIX . '.' . $this->InstanceID . '.SleepMode';
        if (!IPS_VariableProfileExists($profile)) {
            IPS_CreateVariableProfile($profile, 0);
        }
        IPS_SetVariableProfileAssociation($profile, 0, 'Aus', 'Sleep', -1);
        IPS_SetVariableProfileAssociation($profile, 1, 'An', 'Sleep', 0x00FF00);
        $this->RegisterVariableBoolean('SleepMode', 'Ruhe-Modus', $profile, 30);
        $this->EnableAction('SleepMode');

        //Light mode
        $profile = self::MODULE_PREFIX . '.' . $this->InstanceID . '.LightMode';
        if (!IPS_VariableProfileExists($profile)) {
            IPS_CreateVariableProfile($profile, 1);
        }
        IPS_SetVariableProfileIcon($profile, 'Bulb');
        IPS_SetVariableProfileAssociation($profile, 0, 'Aus', 'Bulb', 0x0000FF);
        IPS_SetVariableProfileAssociation($profile, 1, 'Timer', 'Bulb', 0xFFFF00);
        IPS_SetVariableProfileAssociation($profile, 2, 'An', 'Bulb', 0x00FF00);
        $this->RegisterVariableInteger('LightMode', 'Licht', $profile, 40);
        $this->EnableAction('LightMode');

        //Dimmer
        $profile = '~Intensity.100';
        $this->RegisterVariableInteger('Dimmer', 'Helligkeit', $profile, 50);
        $this->EnableAction('Dimmer');

        //Dimming presets
        $profile = self::MODULE_PREFIX . '.' . $this->InstanceID . '.DimmingPresets';
        if (!IPS_VariableProfileExists($profile)) {
            IPS_CreateVariableProfile($profile, 1);
        }
        IPS_SetVariableProfileIcon($profile, 'Menu');
        $this->RegisterVariableInteger('DimmingPresets', 'Helligkeit Voreinstellungen', $profile, 60);
        $this->EnableAction('DimmingPresets');

        //Last brightness
        $profile = '~Intensity.100';
        $this->RegisterVariableInteger('LastBrightness', 'Letzte Helligkeit', $profile, 70);
        IPS_SetIcon($this->GetIDForIdent('LastBrightness'), 'Information');

        //Sleep mode timer
        $this->RegisterVariableString('SleepModeTimer', 'Ruhe-Modus Timer', '', 80);
        IPS_SetIcon($this->GetIDForIdent('SleepModeTimer'), 'Clock');

        //Light mode timer
        $this->RegisterVariableString('DutyCycleTimer', 'Einschaltdauer bis', '', 90);
        $id = $this->GetIDForIdent('DutyCycleTimer');
        IPS_SetIcon($id, 'Clock');

        //Next switching time
        $this->RegisterVariableString('NextSwitchingTime', 'Nächste Schaltzeit', '', 100);
        IPS_SetIcon($this->GetIDForIdent('NextSwitchingTime'), 'Information');

        ########## Timer

        $this->RegisterTimer('SleepMode', 0, self::MODULE_PREFIX . '_DeactivateSleepModeTimer(' . $this->InstanceID . ');');
        $this->RegisterTimer('SwitchLightOff', 0, self::MODULE_PREFIX . '_SwitchLight(' . $this->InstanceID . ', 0, 0, 0);');
        $this->RegisterTimer('SwitchingTimeOne', 0, self::MODULE_PREFIX . '_ExecuteSwitchingTime(' . $this->InstanceID . ', 1);');
        $this->RegisterTimer('SwitchingTimeTwo', 0, self::MODULE_PREFIX . '_ExecuteSwitchingTime(' . $this->InstanceID . ', 2);');
        $this->RegisterTimer('SwitchingTimeThree', 0, self::MODULE_PREFIX . '_ExecuteSwitchingTime(' . $this->InstanceID . ', 3);');
        $this->RegisterTimer('SwitchingTimeFour', 0, self::MODULE_PREFIX . '_ExecuteSwitchingTime(' . $this->InstanceID . ', 4);');
        $this->RegisterTimer('LightUpdate', 0, self::MODULE_PREFIX . '_UpdateLightStatus(' . $this->InstanceID . ');');
    }

    public function ApplyChanges()
    {
        //Wait until IP-Symcon is started
        $this->RegisterMessage(0, IPS_KERNELSTARTED);

        //Never delete this line!
        parent::ApplyChanges();

        //Check runlevel
        if (IPS_GetKernelRunlevel() != KR_READY) {
            return;
        }

        ########## References & Messages

        //Delete all references
        foreach ($this->GetReferenceList() as $referenceID) {
            $this->UnregisterReference($referenceID);
        }

        //Delete all messages
        foreach ($this->GetMessageList() as $senderID => $messages) {
            foreach ($messages as $message) {
                if ($message == VM_UPDATE) {
                    $this->UnregisterMessage($senderID, VM_UPDATE);
                }
                if ($message == EM_UPDATE) {
                    $this->UnregisterMessage($senderID, EM_UPDATE);
                }
            }
        }

        //Light status
        if ($this->ReadPropertyBoolean('UseImmediateLightStatusUpdate')) {
            $id = $this->ReadPropertyInteger('Light');
            if ($id > 1 && @IPS_ObjectExists($id)) {
                $this->RegisterMessage($id, VM_UPDATE);
            }
        }

        //Sunrise
        $id = $this->ReadPropertyInteger('Sunrise');
        if ($id > 1 && @IPS_ObjectExists($id)) {
            $this->RegisterMessage($id, VM_UPDATE);
        }

        //Sunset
        $id = $this->ReadPropertyInteger('Sunset');
        if ($id > 1 && @IPS_ObjectExists($id)) {
            $this->RegisterMessage($id, VM_UPDATE);
        }

        //Weekly schedule
        $id = $this->ReadPropertyInteger('WeeklySchedule');
        if ($id > 1 && @IPS_ObjectExists($id)) {
            $this->RegisterMessage($id, EM_UPDATE);
        }

        //Is day
        $id = $this->ReadPropertyInteger('IsDay');
        if ($id > 1 && @IPS_ObjectExists($id)) {
            $this->RegisterMessage($id, VM_UPDATE);
        }

        //Twilight status
        $id = $this->ReadPropertyInteger('Twilight');
        if ($id > 1 && @IPS_ObjectExists($id)) {
            $this->RegisterMessage($id, VM_UPDATE);
        }

        //Presence status
        $id = $this->ReadPropertyInteger('Presence');
        if ($id > 1 && @IPS_ObjectExists($id)) {
            $this->RegisterMessage($id, VM_UPDATE);
        }

        //Triggers
        $variables = json_decode($this->ReadPropertyString('Triggers'), true);
        foreach ($variables as $variable) {
            if (!$variable['Use']) {
                continue;
            }
            //Primary condition
            if ($variable['PrimaryCondition'] != '') {
                $primaryCondition = json_decode($variable['PrimaryCondition'], true);
                if (array_key_exists(0, $primaryCondition)) {
                    if (array_key_exists(0, $primaryCondition[0]['rules']['variable'])) {
                        $id = $primaryCondition[0]['rules']['variable'][0]['variableID'];
                        if ($id > 1 && @IPS_ObjectExists($id)) {
                            $this->RegisterReference($id);
                            $this->RegisterMessage($id, VM_UPDATE);
                        }
                    }
                }
            }
            //Secondary condition, multi
            if ($variable['SecondaryCondition'] != '') {
                $secondaryConditions = json_decode($variable['SecondaryCondition'], true);
                if (array_key_exists(0, $secondaryConditions)) {
                    if (array_key_exists('rules', $secondaryConditions[0])) {
                        $rules = $secondaryConditions[0]['rules']['variable'];
                        foreach ($rules as $rule) {
                            if (array_key_exists('variableID', $rule)) {
                                $id = $rule['variableID'];
                                if ($id > 1 && @IPS_ObjectExists($id)) {
                                    $this->RegisterReference($id);
                                }
                            }
                        }
                    }
                }
            }
        }

        ########## Presets

        //Dimming presets
        $profile = self::MODULE_PREFIX . '.' . $this->InstanceID . '.DimmingPresets';
        $associations = IPS_GetVariableProfile($profile)['Associations'];
        if (!empty($associations)) {
            foreach ($associations as $association) {
                //Delete
                IPS_SetVariableProfileAssociation($profile, $association['Value'], '', '', -1);
            }
        }
        $dimmingPresets = json_decode($this->ReadPropertyString('DimmingPresets'));
        if (!empty($dimmingPresets)) {
            foreach ($dimmingPresets as $preset) {
                //Create
                IPS_SetVariableProfileAssociation($profile, $preset->DimmingValue, $preset->DimmingText, '', -1);
            }
        }

        ########## Links

        //Sunrise
        $targetID = 0;
        $sunrise = $this->ReadPropertyInteger('Sunrise');
        if ($sunrise > 1 && @IPS_ObjectExists($sunrise)) {
            $targetID = $sunrise;
        }
        $linkID = @IPS_GetLinkIDByName('Nächster Sonnenaufgang', $this->InstanceID);
        if ($targetID > 1 && @IPS_ObjectExists($targetID)) {
            //Check for existing link
            if (!is_int($linkID)) {
                $linkID = IPS_CreateLink();
            }
            IPS_SetParent($linkID, $this->InstanceID);
            IPS_SetPosition($linkID, 110);
            IPS_SetName($linkID, 'Nächster Sonnenaufgang');
            IPS_SetIcon($linkID, 'Sun');
            IPS_SetLinkTargetID($linkID, $targetID);
        } else {
            if (is_int($linkID)) {
                IPS_SetHidden($linkID, true);
            }
        }

        //Sunset
        $targetID = 0;
        $sunset = $this->ReadPropertyInteger('Sunset');
        if ($sunset != 0 && @IPS_ObjectExists($sunset)) {
            $targetID = $sunset;
        }
        $linkID = @IPS_GetLinkIDByName('Nächster Sonnenuntergang', $this->InstanceID);
        if ($targetID != 0 && @IPS_ObjectExists($targetID)) {
            //Check for existing link
            if (!is_int($linkID)) {
                $linkID = IPS_CreateLink();
            }
            IPS_SetParent($linkID, $this->InstanceID);
            IPS_SetPosition($linkID, 120);
            IPS_SetName($linkID, 'Nächster Sonnenuntergang');
            IPS_SetIcon($linkID, 'Moon');
            IPS_SetLinkTargetID($linkID, $targetID);
        } else {
            if (is_int($linkID)) {
                IPS_SetHidden($linkID, true);
            }
        }

        //Weekly schedule
        $targetID = $this->ReadPropertyInteger('WeeklySchedule');
        $linkID = @IPS_GetLinkIDByName('Nächstes Wochenplanereignis', $this->InstanceID);
        if ($targetID != 0 && @IPS_ObjectExists($targetID)) {
            //Check for existing link
            if (!is_int($linkID)) {
                $linkID = IPS_CreateLink();
            }
            IPS_SetParent($linkID, $this->InstanceID);
            IPS_SetPosition($linkID, 130);
            IPS_SetName($linkID, 'Nächstes Wochenplanereignis');
            IPS_SetIcon($linkID, 'Calendar');
            IPS_SetLinkTargetID($linkID, $targetID);
        } else {
            if (is_int($linkID)) {
                IPS_SetHidden($linkID, true);
            }
        }

        //Is day
        $targetID = $this->ReadPropertyInteger('IsDay');
        $linkID = @IPS_GetLinkIDByName('Ist es Tag', $this->InstanceID);
        if ($targetID != 0 && @IPS_ObjectExists($targetID)) {
            //Check for existing link
            if (!is_int($linkID)) {
                $linkID = IPS_CreateLink();
            }
            IPS_SetParent($linkID, $this->InstanceID);
            IPS_SetPosition($linkID, 140);
            IPS_SetName($linkID, 'Ist es Tag');
            IPS_SetLinkTargetID($linkID, $targetID);
        } else {
            if (is_int($linkID)) {
                IPS_SetHidden($linkID, true);
            }
        }

        //Twilight
        $targetID = $this->ReadPropertyInteger('Twilight');
        $linkID = @IPS_GetLinkIDByName('Dämmerung', $this->InstanceID);
        if ($targetID != 0 && @IPS_ObjectExists($targetID)) {
            //Check for existing link
            if (!is_int($linkID)) {
                $linkID = IPS_CreateLink();
            }
            IPS_SetParent($linkID, $this->InstanceID);
            IPS_SetPosition($linkID, 150);
            IPS_SetName($linkID, 'Dämmerung');
            IPS_SetLinkTargetID($linkID, $targetID);
        } else {
            if (is_int($linkID)) {
                IPS_SetHidden($linkID, true);
            }
        }

        //Presence
        $targetID = $this->ReadPropertyInteger('Presence');
        $linkID = @IPS_GetLinkIDByName('Anwesenheit', $this->InstanceID);
        if ($targetID != 0 && @IPS_ObjectExists($targetID)) {
            //Check for existing link
            if (!is_int($linkID)) {
                $linkID = IPS_CreateLink();
            }
            IPS_SetParent($linkID, $this->InstanceID);
            IPS_SetPosition($linkID, 160);
            IPS_SetName($linkID, 'Anwesenheit');
            IPS_SetLinkTargetID($linkID, $targetID);
        } else {
            if (is_int($linkID)) {
                IPS_SetHidden($linkID, true);
            }
        }

        ##########  Options

        //Active
        IPS_SetHidden($this->GetIDForIdent('Active'), !$this->ReadPropertyBoolean('EnableActive'));

        //Automatic mode
        IPS_SetHidden($this->GetIDForIdent('AutomaticMode'), !$this->ReadPropertyBoolean('EnableAutomaticMode'));

        //Sleep mode
        IPS_SetHidden($this->GetIDForIdent('SleepMode'), !$this->ReadPropertyBoolean('EnableSleepMode'));

        //Light mode
        IPS_SetHidden($this->GetIDForIdent('LightMode'), !$this->ReadPropertyBoolean('EnableLightMode'));

        //Dimmer
        IPS_SetHidden($this->GetIDForIdent('Dimmer'), !$this->ReadPropertyBoolean('EnableDimmer'));

        //Dimming Presets
        IPS_SetHidden($this->GetIDForIdent('DimmingPresets'), !$this->ReadPropertyBoolean('EnableDimmingPresets'));

        //Last brightness
        IPS_SetHidden($this->GetIDForIdent('LastBrightness'), !$this->ReadPropertyBoolean('EnableLastBrightness'));
        $manualChange = $this->ReadPropertyBoolean('EnableLastBrightnessManualChange');
        if (!$manualChange) {
            $this->DisableAction('LastBrightness');
        } else {
            $this->EnableAction('LastBrightness');
        }

        //Sleep mode timer
        IPS_SetHidden($this->GetIDForIdent('SleepModeTimer'), !$this->ReadPropertyBoolean('EnableSleepModeTimer'));

        //Light mode timer
        IPS_SetHidden($this->GetIDForIdent('DutyCycleTimer'), !$this->ReadPropertyBoolean('EnableDutyCycleTimer'));

        //Next switching time
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

        //Sunrise
        $id = @IPS_GetLinkIDByName('Nächster Sonnenaufgang', $this->InstanceID);
        if (is_int($id)) {
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

        //Sunset
        $id = @IPS_GetLinkIDByName('Nächster Sonnenuntergang', $this->InstanceID);
        if (is_int($id)) {
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

        //Weekly schedule
        $id = @IPS_GetLinkIDByName('Nächstes Wochenplanereignis', $this->InstanceID);
        if (is_int($id)) {
            $hide = true;
            if ($this->ReadPropertyBoolean('EnableWeeklySchedule')) {
                if ($this->ValidateWeeklySchedule()) {
                    $hide = false;
                }
            }
            IPS_SetHidden($id, $hide);
        }

        //Is day
        $id = @IPS_GetLinkIDByName('Ist es Tag', $this->InstanceID);
        if (is_int($id)) {
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

        //Twilight
        $id = @IPS_GetLinkIDByName('Dämmerung', $this->InstanceID);
        if (is_int($id)) {
            $hide = true;
            $targetID = $this->ReadPropertyInteger('Twilight');
            if ($targetID != 0 && @IPS_ObjectExists($targetID)) {
                if ($this->ReadPropertyBoolean('EnableTwilight')) {
                    $hide = false;
                }
            }
            IPS_SetHidden($id, $hide);
        }

        //Presence
        $id = @IPS_GetLinkIDByName('Anwesenheit', $this->InstanceID);
        if (is_int($id)) {
            $hide = true;
            $targetID = $this->ReadPropertyInteger('Presence');
            if ($targetID != 0 && @IPS_ObjectExists($targetID)) {
                if ($this->ReadPropertyBoolean('EnablePresence')) {
                    $hide = false;
                }
            }
            IPS_SetHidden($id, $hide);
        }

        ########## Timer

        $this->DeactivateSleepModeTimer();
        $this->DeactivateDutyCycleTimer();
        $this->SetSwitchingTimes();
        $this->SetTimerInterval('LightUpdate', $this->ReadPropertyInteger('LightStatusUpdateInterval') * 1000);

        ########## Update

        $this->UpdateLightStatus();

        ########## Maintenance

        $this->CheckMaintenance();
    }

    public function Destroy()
    {
        //Never delete this line!
        parent::Destroy();

        //Delete profiles
        $profiles = ['AutomaticMode', 'SleepMode', 'LightMode', 'DimmingPresets'];
        foreach ($profiles as $profile) {
            $profileName = self::MODULE_PREFIX . '.' . $this->InstanceID . '.' . $profile;
            if (@IPS_VariableProfileExists($profileName)) {
                IPS_DeleteVariableProfile($profileName);
            }
        }
    }

    public function MessageSink($TimeStamp, $SenderID, $Message, $Data)
    {
        $this->SendDebug(__FUNCTION__, $TimeStamp . ', SenderID: ' . $SenderID . ', Message: ' . $Message . ', Data: ' . print_r($Data, true), 0);
        if (!empty($Data)) {
            foreach ($Data as $key => $value) {
                $this->SendDebug(__FUNCTION__, 'Data[' . $key . '] = ' . json_encode($value), 0);
            }
        }
        switch ($Message) {
            case IPS_KERNELSTARTED:
                $this->KernelReady();
                break;

            case VM_UPDATE:

                //$Data[0] = actual value
                //$Data[1] = value changed
                //$Data[2] = last value
                //$Data[3] = timestamp actual value
                //$Data[4] = timestamp value changed
                //$Data[5] = timestamp last value

                if ($this->CheckMaintenance()) {
                    return;
                }

                //Light
                $light = $this->ReadPropertyInteger('Light');
                if ($light > 1 && @IPS_ObjectExists($light)) {
                    if ($SenderID == $light) {
                        if ($Data[1]) {
                            /*
                            $scriptText = self::MODULE_PREFIX . '_UpdateLightStatus(' . $this->InstanceID . ');';
                            IPS_RunScriptText($scriptText);
                             */
                            $this->UpdateLightStatus();
                        }
                    }
                }

                //Sunrise
                $sunrise = $this->ReadPropertyInteger('Sunrise');
                if ($sunrise > 1 && @IPS_ObjectExists($sunrise)) {
                    if ($SenderID == $sunrise) {
                        if ($Data[1]) {
                            /*
                            $scriptText = self::MODULE_PREFIX . '_ExecuteSunriseSunsetAction(' . $this->InstanceID . ', ' . $SenderID . ', 0);';
                            IPS_RunScriptText($scriptText);
                             */
                            $this->ExecuteSunriseSunsetAction($SenderID, 0);
                        }
                    }
                }

                // Sunset
                $sunset = $this->ReadPropertyInteger('Sunset');
                if ($sunset > 1 && @IPS_ObjectExists($sunset)) {
                    if ($SenderID == $sunset) {
                        if ($Data[1]) {
                            /*
                            $scriptText = self::MODULE_PREFIX . '_ExecuteSunriseSunsetAction(' . $this->InstanceID . ', ' . $SenderID . ', 1);';
                            IPS_RunScriptText($scriptText);
                             */
                            $this->ExecuteSunriseSunsetAction($SenderID, 1);
                        }
                    }
                }

                ///Is day
                $id = $this->ReadPropertyInteger('IsDay');
                if ($id > 1 && @IPS_ObjectExists($id)) {
                    if ($SenderID == $id) {
                        if ($Data[1]) {
                            /*
                            $scriptText = self::MODULE_PREFIX . '_ExecuteIsDayDetection(' . $this->InstanceID . ');';
                            IPS_RunScriptText($scriptText);
                             */
                            $this->ExecuteIsDayDetection();
                        }
                    }
                }

                //Twilight
                $id = $this->ReadPropertyInteger('Twilight');
                if ($id > 1 && @IPS_ObjectExists($id)) {
                    if ($SenderID == $id) {
                        if ($Data[1]) {
                            /*
                            $scriptText = self::MODULE_PREFIX . '_ExecuteTwilightDetection(' . $this->InstanceID . ');';
                            IPS_RunScriptText($scriptText);
                             */
                            $this->ExecuteTwilightDetection();
                        }
                    }
                }

                //Presence
                $id = $this->ReadPropertyInteger('Presence');
                if ($id > 1 && @IPS_ObjectExists($id)) {
                    if ($SenderID == $id) {
                        if ($Data[1]) {
                            /*
                            $scriptText = self::MODULE_PREFIX . '_ExecutePresenceDetection(' . $this->InstanceID . ');';
                            IPS_RunScriptText($scriptText);
                             */
                            $this->ExecutePresenceDetection();
                        }
                    }
                }

                //Triggers
                $triggers = json_decode($this->ReadPropertyString('Triggers'), true);
                if (!empty($triggers)) {
                    $this->SendDebug(__FUNCTION__, 'We are here', 0);
                    /*
                    $scriptText = self::MODULE_PREFIX . '_CheckTrigger(' . $this->InstanceID . ', ' . $SenderID . ');';
                    IPS_RunScriptText($scriptText);
                     */
                    $this->CheckTriggerConditions($SenderID, boolval($Data[1]));
                }
                break;

            case EM_UPDATE:

                if ($this->CheckMaintenance()) {
                    return;
                }

                //$Data[0] = last run
                //$Data[1] = next run

                ///Weekly schedule
                /*
                $scriptText = self::MODULE_PREFIX . '_ExecuteWeeklyScheduleAction(' . $this->InstanceID . ');';
                IPS_RunScriptText($scriptText);
                 */
                $this->ExecuteWeeklyScheduleAction();
                break;

        }
    }

    public function CreateCommandControlInstance(): void
    {
        $id = IPS_CreateInstance(self::ABLAUFSTEUERUNG_MODULE_GUID);
        if (is_int($id)) {
            IPS_SetName($id, 'Ablaufsteuerung');
            $infoText = 'Instanz mit der ID ' . $id . ' wurde erfolgreich erstellt!';
        } else {
            $infoText = 'Instanz konnte nicht erstellt werden!';
        }
        $this->UpdateFormField('InfoMessage', 'visible', true);
        $this->UpdateFormField('InfoMessageLabel', 'caption', $infoText);
    }

    /**
     * Creates an example script.
     * @return void
     */
    public function CreateScriptExample(): void
    {
        $scriptID = IPS_CreateScript(0);
        IPS_SetName($scriptID, 'Beispielskript (Lichtsteuerung #' . $this->InstanceID . ')');
        $scriptContent = "<?php\n\n// Methode:\n// LS_SwitchLight(integer \$InstanceID, integer \$Brightness, integer \$DutyCycle, integer \$DutyCycleUnit);\n\n### Beispiele:\n\n// Licht ausschalten:\nLS_SwitchLight(" . $this->InstanceID . ", 0, 0, 0);\n\n// Licht für 180 Sekunden einschalten:\nLS_SwitchLight(" . $this->InstanceID . ", 100, 180, 0);\n\n// Licht für 5 Minuten einschalten:\nLS_SwitchLight(" . $this->InstanceID . ", 100, 5, 1);\n\n// Licht mit 50 % Helligkeit einschalten:\nLS_SwitchLight(" . $this->InstanceID . ', 50, 0, 0);';
        IPS_SetScriptContent($scriptID, $scriptContent);
        IPS_SetParent($scriptID, $this->InstanceID);
        IPS_SetPosition($scriptID, 200);
        IPS_SetHidden($scriptID, true);
        if ($scriptID != 0) {
            $infoText = 'Beispielskript wurde erfolgreich erstellt!';
            $this->UpdateFormField('InfoMessage', 'visible', true);
            $this->UpdateFormField('InfoMessageLabel', 'caption', $infoText);
        }
    }

    #################### Request Action

    public function RequestAction($Ident, $Value)
    {
        switch ($Ident) {
            case 'Active':
            case 'AutomaticMode':
                $this->SetValue($Ident, $Value);
                break;

            case 'SleepMode':
                $this->SetValue($Ident, $Value);
                if ($Value) {
                    //Duration from hours to seconds
                    $duration = $this->ReadPropertyInteger('SleepDuration') * 60 * 60;
                    //Set timer interval
                    $this->SetTimerInterval('SleepMode', $duration * 1000);
                    $timestamp = time() + $duration;
                    $this->SetValue('SleepModeTimer', date('d.m.Y, H:i:s', ($timestamp)));
                } else {
                    $this->DeactivateSleepModeTimer();
                }
                break;

            case 'LightMode':
                switch ($Value) {
                    //Off
                    case 0:
                        $settings = json_decode($this->ReadPropertyString('LightOff'), true);
                        $action = true;
                        $mode = 0;
                        break;

                    //Timer
                    case 1:
                        $settings = json_decode($this->ReadPropertyString('Timer'), true);
                        $action = true;
                        $mode = 1;
                        break;

                    //On
                    case 2:
                        $settings = json_decode($this->ReadPropertyString('LightOn'), true);
                        $action = true;
                        $mode = 3;
                        break;

                }

                //Trigger action
                if (isset($action) && isset($mode) && $action) {
                    if (!empty($settings)) {
                        foreach ($settings as $setting) {
                            if ($setting['UseSettings']) {
                                $brightness = intval($setting['Brightness']);
                                //Check conditions
                                $checkConditions = $this->CheckAllConditions(json_encode($setting));
                                if (!$checkConditions) {
                                    return;
                                }
                                $this->SetValue('LightMode', $mode);
                                if ($setting['UpdateLastBrightness']) {
                                    $this->SetValue('LastBrightness', $brightness);
                                }
                                $duration = 0;
                                $durationUnit = 0;
                                if ($mode == 1) { //Timer
                                    $duration = $setting['DutyCycle'];
                                    $durationUnit = $setting['DutyCycleUnit'];
                                }
                                $this->SwitchLight($brightness, $duration, $durationUnit);
                            }
                        }
                    }
                }
                break;

            case 'Dimmer':
                if ($this->ReadPropertyBoolean('DimmerUpdateLastBrightness')) {
                    $this->SetValue('LastBrightness', $Value);
                }
                $this->SwitchLight(intval($Value));
                break;

            case 'DimmingPresets':
                if ($this->ReadPropertyBoolean('DimmingPresetsUpdateLastBrightness')) {
                    $this->SetValue('LastBrightness', $Value);
                }
                $this->SwitchLight(intval($Value));
                break;

        }
    }

    #################### Private

    private function KernelReady()
    {
        $this->ApplyChanges();
    }

    private function CheckMaintenance(): bool
    {
        $result = false;
        if (!$this->GetValue('Active')) {
            $this->SendDebug(__FUNCTION__, 'Abbruch, die Instanz ist inaktiv!', 0);
            $result = true;
        }
        return $result;
    }

    /**
     * Checks for an activated action.
     *
     * @param string $PropertyVariableName
     * @param string $PropertyActionName
     * @return bool
     * false =  no activated action available
     * true =   activate action
     *
     * @throws Exception
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

    /**
     * Attempts to set a semaphore and repeats this up to 100 times if unsuccessful.
     * @param string $Name
     * @return bool
     */
    private function LockSemaphore(string $Name): bool
    {
        for ($i = 0; $i < 100; $i++) {
            if (IPS_SemaphoreEnter(self::MODULE_PREFIX . '_' . $this->InstanceID . '_Semaphore_' . $Name, 1)) {
                $this->SendDebug(__FUNCTION__, 'Semaphore locked', 0);
                return true;
            } else {
                IPS_Sleep(mt_rand(1, 5));
            }
        }
        return false;
    }

    /**
     * Unlocks a semaphore.
     * @param string $Name
     */
    private function UnlockSemaphore(string $Name): void
    {
        IPS_SemaphoreLeave(self::MODULE_PREFIX . '_' . $this->InstanceID . '_Semaphore_' . $Name);
        $this->SendDebug(__FUNCTION__, 'Semaphore unlocked', 0);
    }
}