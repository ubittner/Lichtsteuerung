<?php

/*
 * @module      Lichtsteuerung
 *
 * @prefix      LS
 *
 * @file        module.php
 *
 * @developer   Ulrich Bittner
 * @project     Ulrich Bittner
 * @copyright   (c) 2019
 * @license     CC BY-NC-SA 4.0
 *              https://creativecommons.org/licenses/by-nc-sa/4.0/
 *
 * @version     1.00-1
 * @date        2019-05-18, 18:00
 * @lastchange  2019-05-18, 18:00
 *
 * @see         https://git.ubittner.de/ubittner/Lichtsteuerung
 *
 * @guids       Library
 *              {46195F9D-0325-41E2-B83B-A1192293BE4E}
 *
 *              Lichtsteuerung
 *             	{14AE3EA4-76F6-4A36-BE98-628A4CB1EAC7}
 *
 * @changelog   2019-05-18, 18:00, initial version 1.00-1
 *
 */

// Declare
declare(strict_types=1);

// Include
include_once __DIR__ . '/helper/LS_autoload.php';

class Lichtsteuerung extends IPSModule
{
    // Helper
    use LS_backupRestore;
    use LS_switchLights;
    use LS_timer;

    public function Create()
    {
        // Never delete this line!
        parent::Create();

        //#################### Register properties

        // Astro switch on
        $this->RegisterPropertyBoolean('UseSwitchOnAstro', false);
        $this->RegisterPropertyInteger('SwitchOnAstro', 0);
        // Time switch on
        $this->RegisterPropertyBoolean('UseSwitchOnTime', false);
        $this->RegisterPropertyString('SwitchOnTime', '{"hour":22,"minute":30,"second":0}');
        $this->RegisterPropertyBoolean('UseRandomSwitchOnDelay', false);
        $this->RegisterPropertyInteger('SwitchOnDelay', 30);
        // Astro switch off
        $this->RegisterPropertyBoolean('UseSwitchOffAstro', false);
        $this->RegisterPropertyInteger('SwitchOffAstro', 0);
        // Time switch off
        $this->RegisterPropertyBoolean('UseSwitchOffTime', false);
        $this->RegisterPropertyString('SwitchOffTime', '{"hour":8,"minute":30,"second":0}');
        $this->RegisterPropertyBoolean('UseRandomSwitchOffDelay', false);
        $this->RegisterPropertyInteger('SwitchOffDelay', 30);
        // Lights
        $this->RegisterPropertyString('Lights',  '[]');
        // Backup / Restore
        $this->RegisterPropertyInteger('BackupCategory', 0);
        $this->RegisterPropertyInteger('Configuration', 0);

        //#################### Register variables

        // Lights
        $this->RegisterVariableBoolean('Lights', 'Beleuchtung', '~Switch', 1);
        $this->EnableAction('Lights');
        // AutomaticMode
        $this->RegisterVariableBoolean('AutomaticMode', 'Automatik', '~Switch', 2);
        IPS_SetIcon($this->GetIDForIdent('AutomaticMode'), 'Clock');
        $this->EnableAction('AutomaticMode');
        // Next switch on time
        $this->RegisterVariableString('NextSwitchOnTime', 'Nächste Einschaltzeit', '', 3);
        IPS_SetIcon($this->GetIDForIdent('NextSwitchOnTime'), 'Information');
        // Next switch off time
        $this->RegisterVariableString('NextSwitchOffTime', 'Nächste Ausschaltzeit', '', 4);
        IPS_SetIcon($this->GetIDForIdent('NextSwitchOffTime'), 'Information');

        //#################### Register timer

        $this->RegisterTimer('SwitchLightsOn', 0, 'LS_SwitchLights($_IPS[\'TARGET\'], true, "Timer");');
        $this->RegisterTimer('SwitchLightsOff', 0, 'LS_SwitchLights($_IPS[\'TARGET\'], false, "Timer");');
    }

    public function ApplyChanges()
    {
        // Register messages
        // Base
        $this->RegisterMessage(0, IPS_KERNELSTARTED);

        // Never delete this line!
        parent::ApplyChanges();

        // Check runlevel
        if (IPS_GetKernelRunlevel() != KR_READY) {
            return;
        }

        // Check instance
        if (IPS_GetInstance($this->InstanceID)['InstanceStatus'] == 102) {
            // Set timer
            $this->SetNextTimer();
            // Register lights
            $this->RegisterLights();
            // Create Links
            $this->CreateLightsLink();
            // Check lights state
            $this->CheckLightsState();
        }
    }

    public function MessageSink($TimeStamp, $SenderID, $Message, $Data)
    {
        $this->SendDebug('MessageSink', 'SenderID: ' . $SenderID . ', Message: ' . $Message, 0);
        switch ($Message) {
            case IPS_KERNELSTARTED:
                $this->KernelReady();
                break;
            case VM_UPDATE:
                $this->CheckLightsState();
                break;
            default:
                break;
        }
    }

    /**
     * Applies changes when the kernel is ready.
     */
    protected function KernelReady()
    {
        $this->ApplyChanges();
    }

    /**
     * Creates the links for the assigned lights.
     */
    protected function CreateLightsLink()
    {
        // Create new array from assigned and used lights list
        // key = position
        // value = target id
        $targetIDs = [];
        $devices = json_decode($this->ReadPropertyString('Lights'));
        if (!empty($devices)) {
            foreach ($devices as $device) {
                if ($device->UseLight) {
                    $targetIDs[$device->Position] = $device->VariableID;
                }
            }
        }
        // Create new array from existing lights links
        // key = link id
        // value = existing target id
        $existingTargetIDs = [];
        $childrenIDs = IPS_GetChildrenIDs($this->InstanceID);
        foreach ($childrenIDs as $childID) {
            // Check if child is a link
            $objectType = IPS_GetObject($childID)['ObjectType'];
            if ($objectType == 6) {
                // Get target id
                $existingTargetID = IPS_GetLink($childID)['TargetID'];
                $existingTargetIDs[$childID] = $existingTargetID;
            }
        }
        // Delete dead links
        $deadLinks = array_diff($existingTargetIDs, $targetIDs);
        foreach ($deadLinks as $linkID => $existingTargetID) {
            if (IPS_LinkExists($linkID)) {
                IPS_DeleteLink($linkID);
            }
        }
        // Create new links
        $newLinks = array_diff($targetIDs, $existingTargetIDs);
        foreach ($newLinks as $position => $targetID) {
            $linkID = IPS_CreateLink();
            IPS_SetParent($linkID, $this->InstanceID);
            if (!empty($position)) {
                IPS_SetPosition($linkID, $position + 4);
                IPS_SetName($linkID, $devices[$position - 1]->Description);
                IPS_SetIcon($linkID, 'Bulb');
            }
            IPS_SetLinkTargetID($linkID, $targetID);
        }
        // Edit existing links
        $existingLinks = array_intersect($existingTargetIDs, $targetIDs);
        foreach ($existingLinks as $linkID => $targetID) {
            $position = array_search($targetID, $targetIDs);
            if (!empty($position)) {
                IPS_SetPosition($linkID, $position + 4);
                IPS_SetName($linkID, $devices[$position - 1]->Description);
                IPS_SetIcon($linkID, 'Bulb');
            }
        }
    }

    //#################### Request action

    public function RequestAction($Ident, $Value)
    {
        switch ($Ident) {
            case 'Lights':
                $this->SwitchLights($Value, 'Switch');
                break;
            case 'AutomaticMode':
                $this->SetAutomaticMode($Value);
                break;
            default:
                break;
        }
    }
}