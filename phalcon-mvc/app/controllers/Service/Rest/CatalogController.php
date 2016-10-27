<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    CatalogController.php
// Created: 2015-04-07 13:10:10
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Controllers\Service\Rest;

use OpenExam\Controllers\Service\RestController;
use OpenExam\Library\WebService\Handler\CatalogHandler;

/**
 * REST controller for catalog (directory information) service.
 * 
 * CRUD operations:
 * -------------------------
 * 
 * catalog/
 *   +-- attribute      GET,POST
 *   +-- domains        GET
 *   +-- groups         GET,POST
 *   +-- mail           GET,POST
 *   +-- name           GET,POST
 *   +-- principal      GET,POST
 * 
 * Request parameters:
 * -------------------------
 * 
 * Request parameters is passed either encoded in the URL or as POST parameters. 
 * These two commands demonstrate this principle:
 * 
 * curl -XGET  ${BASEURL}/rest/catalog/mail/principal/user@example.com
 * curl -XPOST ${BASEURL}/rest/catalog/mail -d '{"principal":"user@example.com"}'
 * 
 * The complete POST payload has this form: '{"data":{...},"params":{...}'. 
 * If data is omitted, then the payload is assumed to be all data.
 * 
 * For operations accepting principal names, if the principal name is missing,
 * then the calling user is used:
 * 
 * curl -XGET  ${BASEURL}/rest/catalog/mail     // Get email for calling user
 * 
 * This controller supports the same parameters, operations and filtering of
 * response as the AJAX catalog controller. The difference is only in how data
 * is input and response is encoded (HTTP status).
 * 
 * Complex example:
 * -------------------------
 * 
 * This is a complete example for query user principals in the example.com
 * domain, limit returned to 5. The principal is searched on 'Anders' as 
 * given name requesting the user principal name, email and UID as returned
 * attributes:
 * 
 * curl -XGET  ${BASEURL}/rest/catalog/principal -d \
 *      '{"data":{"gn":"Anders"},"params":{"attr":["principal","mail","uid"],"domain":"example.com","limit":5}}'
 * 
 * @see \OpenExam\Controllers\Service\Ajax\CatalogController
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class CatalogController extends RestController
{

        /**
         *
         * @var CatalogHandler 
         */
        private $_handler;

        public function initialize()
        {
                parent::initialize();
                $this->_handler = new CatalogHandler($this->getRequest(), $this->user, $this->catalog);
        }

        public function indexAction()
        {
                
        }

        /**
         * List all domains.
         */
        public function domainsAction()
        {
                if ($this->request->isGet()) {
                        $response = $this->_handler->domains();
                        $this->sendResponse($response);
                }
        }

        /**
         * Get name from user principal.
         */
        public function nameAction()
        {
                if ($this->request->isGet() || $this->request->isPost()) {
                        $response = $this->_handler->name();
                        $this->sendResponse($response);
                }
        }

        /**
         * Get mail address from user principal.
         */
        public function mailAction()
        {
                if ($this->request->isGet() || $this->request->isPost()) {
                        $response = $this->_handler->mail();
                        $this->sendResponse($response);
                }
        }

        /**
         * Get attribute from user principal.
         */
        public function attributeAction()
        {
                if ($this->request->isGet() || $this->request->isPost()) {
                        $response = $this->_handler->attribute();
                        $this->sendResponse($response);
                }
        }

        /**
         * Get user principal groups (GET and POST).
         * @param string $principal The user principal.
         * @param string $output The output format.
         */
        public function groupsAction($principal = null, $output = null)
        {
                if ($this->request->isGet() || $this->request->isPost()) {
                        $response = $this->_handler->groups($this->request->getMethod(), $principal, $output);
                        $this->sendResponse($response);
                }
        }

        /**
         * Get group members (GET and POST).
         * @param string $group The group name.
         * @param string $output The output format.
         */
        public function membersAction($group = null, $output = null)
        {
                if ($this->request->isGet() || $this->request->isPost()) {
                        $response = $this->_handler->members($this->request->getMethod(), $group, $output);
                        $this->sendResponse($response);
                }
        }

        /**
         * Search for user principals.
         */
        public function principalAction()
        {
                if ($this->request->isGet() || $this->request->isPost()) {
                        $response = $this->_handler->principal();
                        $this->sendResponse($response);
                }
        }

}