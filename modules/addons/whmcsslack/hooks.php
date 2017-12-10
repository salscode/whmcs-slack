<?php

if (!defined('WHMCS'))
    die('This file cannot be accessed directly');

use WHMCS\Database\Capsule;

/**
 * Log module call.
 *
 * @param string $module The name of the module
 * @param string $action The name of the action being performed
 * @param string|array $requestString The input parameters for the API call
 * @param string|array $responseData The response data from the API call
 * @param string|array $processedData The resulting data after any post processing (eg. json decode, xml decode, etc...)
 * @param array $replaceVars An array of strings for replacement

 * logModuleCall($module, $action, $requestString, $responseData, $processedData, $replaceVars);
*/

function whmcsslack_getconfig()
{
  $settings = Capsule::table('tbladdonmodules')
    ->selectRaw("setting,value")
    ->where('module', '=', 'whmcsslack')
    ->get();

  foreach ($settings as $item) {
    $ret[$item->setting] = $item->value;
  }

  logModuleCall("whmcsslack", "whmcsslack_getconfig", "", "", print_r($ret, true), null);
  return $ret;
}

add_hook("ClientAdd", 1, "whmcsslack_ClientAdd");
function whmcsslack_ClientAdd($vars)
{
    global $customadminpath, $CONFIG;
    $config = whmcsslack_getconfig();
    if ($config == null || empty($config['webhook']))
        return;
    if (!$config['new_client'])
        return;
    $url = $CONFIG['SystemURL'] . '/' . $customadminpath . '/clientssummary.php?userid=' . $vars['userid'];
    $data = [];
    $data['text'] = 'A new client has signed up! ' .
        '<' . $url . '|Click here> for details!';
    whmcsslack_call($config['webhookUrl'], $data);
}

add_hook("InvoicePaid", 1, "whmcsslack_InvoicePaid");
function whmcsslack_InvoicePaid($vars)
{
    global $customadminpath, $CONFIG;
    $config = whmcsslack_getconfig();
    if ($config == null || empty($config['webhookUrl']))
        return;
    if (!$config['new_invoice'])
        return;
    $url = $CONFIG['SystemURL'] . '/' . $customadminpath . '/invoices.php?action=edit&id=' . $vars['invoiceid'];
    $data = [];
    $data['text'] = 'Invoice ' . $vars['invoiceid'] . ' has just been paid! ' .
        '<' . $url . '|Click here> for details!';
    whmcsslack_call($config['webhookUrl'], $data);
}

add_hook("TicketOpen", 1, "whmcsslack_TicketOpen");
function whmcsslack_TicketOpen($vars)
{
    whmcsslack_TicketChange('TicketOpen', $vars);
}

add_hook("TicketUserReply", 1, "whmcsslack_TicketUserReply");
function whmcsslack_TicketUserReply($vars)
{
    whmcsslack_TicketChange('TicketUserReply', $vars);
}

add_hook("TicketAdminReply", 1, "whmcsslack_TicketAdminReply");
function whmcsslack_TicketAdminReply($vars)
{
    whmcsslack_TicketChange('TicketAdminReply', $vars);
}

// Gets a user's display name.
function whmcsslack_GetUserDisplayName($userid)
{
    $result = select_query('tblclients', 'firstname, lastname', ['id' => $userid]);
    if (mysql_num_rows($result) == 0)
        return '';

    $data = mysql_fetch_array($result);
    return $data['firstname'].' '.$data['lastname'];
}

// Gets an admin's display name.
function whmcsslack_GetAdminDisplayName($userid)
{
    $result = select_query('tbladmins', 'firstname, lastname', ['id' => $userid]);
    if (mysql_num_rows($result) == 0)
        return '';

    $data = mysql_fetch_array($result);
    return $data['firstname'].' '.$data['lastname'];
}

// Gets flagged admin name.
function whmcsslack_GetFlaggedTicketAdmin($ticketid)
{
    $result = select_query('tbltickets', 'flag', ['id' => $ticketid]);
    if (mysql_num_rows($result) == 0)
        return '';

    $data = mysql_fetch_array($result);
    return whmcsslack_GetAdminDisplayName($data['flag']);
}

function whmcsslack_TicketChange($status, $vars)
{
    global $customadminpath, $CONFIG;
    $config = whmcsslack_getconfig();
    if ($config == null || empty($config['webhookUrl']))
        return;

    $data = array();
    $attachement = array();
    $attachement['title'] = 'Ticket #' . $vars['ticketid'] . ': ' . $vars['subject'];
    $attachement['title_link'] = $CONFIG['SystemURL'].'/'.$customadminpath.'/supporttickets.php?action=viewticket&id='.$vars['ticketid'];
    $attachement['text'] = substr($vars['message'], 0, 50) . '…';
    $attachement['color'] = "#7CD197";
    $attachement['fields'] = array();
    $attachement['fields'][] = array("title" => "Department", "value" => $vars['deptname'], "short" => true);
    $attachement['fields'][] = array("title" => "Priority", "value" => $vars['priority'], "short" => true);

    $clientName = whmcsslack_GetUserDisplayName($vars['userid']);
    if ($clientName != "")
      $clientName = " from {$clientName}";
    else
      $clientName = ".";

    switch ($status) {
        case 'TicketUserReply':
            if (!$config['new_update'])
                return;
            $attachement['pretext'] = "New ticket reply" . $clientName;
            break;
        case 'TicketAdminReply':
            if (!$config['new_update_admin'])
                return;
            $attachement['pretext'] = "New admin reply from " . $vars['admin'];
            break;
        case 'TicketOpen':
            if (!$config['new_ticket'])
                return;
            $attachement['pretext'] = "New ticket" . $clientName;
            break;
        default:
            return;
    }

    $flaggedAdmin = whmcsslack_GetFlaggedTicketAdmin($vars['ticketid']);
  	if ($flaggedAdmin != "")
  		$attachement['fields'][] = array("title" => "Assigned", "value" => $flaggedAdmin, "short" => true);

    $data['attachments'] = [$attachement];
    whmcsslack_call($config['webhookUrl'], $data);
}

function whmcsslack_call($webhookUrl, $data)
{
    $payload = ['payload' => json_encode($data)];
    $response = curlCall($webhookUrl, $payload);
}
