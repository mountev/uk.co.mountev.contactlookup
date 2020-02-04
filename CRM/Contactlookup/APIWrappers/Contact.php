<?php

class CRM_Contactlookup_APIWrappers_Contact implements API_Wrapper {

  /**
   * Conditionally changes contact_type parameter for the API request.
   */
  public function fromApiInput($apiRequest) {
    return $apiRequest;
  }

  /**
   * Modify result of contact getlist api before it is returned - so it also contains
   * contain ID search result.
   * 
   */
  public function toApiOutput($apiRequest, $result) {
    if (!empty($apiRequest['params']['input']) && CRM_Utils_Rule::positiveInteger($apiRequest['params']['input'])) {
      $entity   = 'Contact';
      $request  = $apiRequest['params'];

      // Follow generic_getlist approach so when we inject into the result - it also has 
      // descriptions, image etc based on search preferences
      $meta     = civicrm_api3_generic_getfields(['action' => 'get'] + $apiRequest, FALSE);
      $fnName   = "_civicrm_api3_{$entity}_getlist_defaults";
      $defaults = function_exists($fnName) ? $fnName($request) : [];
      _civicrm_api3_generic_getList_defaults('Contact', $request, $defaults, $meta['values']);

      $fnName = "_civicrm_api3_{$entity}_getlist_params";
      $fnName = function_exists($fnName) ? $fnName : '_civicrm_api3_generic_getlist_params';
      $fnName($request);

      if (!empty($request['params']['sort_name']) && CRM_Utils_Rule::positiveInteger($request['params']['sort_name'])) {
        $request['params']['id'] = $request['params']['sort_name'];
        unset($request['params']['sort_name']);

        // Make sure to enforce permission check - similar to sort-name
        $request['params']['check_permissions'] = !empty($apiRequest['params']['check_permissions']);
        $result2 = civicrm_api3($entity, 'get', $request['params']);

        $fnName = "_civicrm_api3_{$entity}_getlist_output";
        $fnName = function_exists($fnName) ? $fnName : '_civicrm_api3_generic_getlist_output';
        $values = $fnName($result2, $request, $entity, $meta['values']);

        _civicrm_api3_generic_getlist_postprocess($result2, $request, $values);

        if (!empty($values[0])) {
          if ($values[0]['id'] == $request['params']['id']) {
            $values[0]['description'][] = ts('Contact ID') . ': ' . $request['params']['id'];
          }
          array_unshift($result['values'], $values[0]);
        }
      }
    }
    return $result;
  }
}
