<?php

class CRM_SendGrid_Utils {

  /**
   * Look up a job by ID.
   * @param int $jobId
   *   The ID number of the job.
   * @return array
   *   An array containing at least 'mailing_id' => [the mailing ID]
   */
  public static function getJobById($jobId) {
    $jobCache = Civi::cache()->get('sendgridJobCache') ?: array();

    if (empty($jobCache[$jobId])) {
      $jobCache[$jobId] = civicrm_api3('MailingJob', 'getsingle', array(
        'id' => $jobId,
        'return' => 'mailing_id',
      ));
      Civi::cache()->set('sendgridJobCache', $jobCache);
    }
    return $jobCache[$jobId];
  }

  /**
   * Look up a mailing by the job ID.
   *
   * @param int $jobId
   *   The ID number of a job.
   * @return array
   *   The result of Mailing.getsingle.
   */
  public static function getMailingByJob($jobId) {
    $job = self::getJobById($jobId);
    $mailingCache = Civi::cache()->get('sendgridMailingCache') ?: array();
    if (empty($mailingCache[$job['mailing_id']])) {
      $mailingCache[$job['mailing_id']] = civicrm_api3('Mailing', 'getsingle', array('id' => $job['mailing_id']));
      Civi::cache()->set('sendgridMailingCache', $mailingCache);
    }
    return $mailingCache[$job['mailing_id']];
  }

  public static function getSettings() {
    $settings = Civi::cache()->get('sendgridSettings');

    if (empty($settings)) {
      $settings = array(
        'secretcode' => NULL,
        'open_click_processor' => NULL,
        'track_optional' => NULL,
      );
      foreach ($settings as $setting => $val) {
        try {
          $settings[$setting] = civicrm_api3('Setting', 'getvalue', array(
            'name' => "sendgrid_$setting",
            'group' => 'Sendgrid Preferences',
          ));
        }
        catch (CiviCRM_API3_Exception $e) {
          $error = $e->getMessage();
          CRM_Core_Error::debug_log_message(ts('API Error: %1', array(
            1 => $error,
            'domain' => 'com.aghstrategies.sendgrid',
          )));
        }
      }
      Civi::cache()->set('sendgridSettings', $settings);
    }
    return $settings;
  }

}
