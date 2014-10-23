<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    DirectoryManager.php
// Created: 2014-10-22 03:44:35
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Catalog;

/**
 * Directory service manager.
 * 
 * This class maintains a register of directory services grouped by domains
 * and provides uniform queries against all services registered for a single
 * domain or all domains at once.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class DirectoryManager implements DirectoryService
{

        /**
         * Default list of attributes returned.
         */
        const DEFAULT_ATTR = array(Principal::ATTR_UID, Principal::ATTR_CN, Principal::ATTR_MAIL);
        /**
         * Default search attribute.
         */
        const DEFAULT_SEARCH = Principal::ATTR_CN;
        /**
         * Default limit on number of returned user principal objects.
         */
        const DEFAULT_LIMIT = 5;

        /**
         * The collection of directory services.
         * @var array 
         */
        private $services;
        /**
         * Default search domain.
         * @var string 
         */
        private $domain;

        /**
         * Constructor.
         * @param DirectoryService[] $services The collection of directory services.
         */
        public function __construct($services = array())
        {
                $this->services = $services;
        }

        /**
         * Destructor.
         */
        public function __destruct()
        {
                foreach ($this->services as $domain => $services) {
                        foreach ($services as $service) {
                                if ($service->connected()) {
                                        try {
                                                $service->close();
                                        } catch (Exception $exception) {
                                                $this->logger->system->error($exception->getMessage());
                                        }
                                }
                        }
                }
        }

        /**
         * Register an directory service.
         * 
         * This function register an direcory service as a catalog for
         * one or more domains.
         *  
         * @param array $domains The domains.
         * @param DirectoryService $service The directory service.
         */
        public function register($service, $domains)
        {
                foreach ($domains as $domain) {
                        if (!isset($this->services[$domain])) {
                                $this->services[$domain] = array();
                        }
                        $this->services[$domain][] = $service;
                }
        }

        /**
         * Get registered domains.
         * @return array
         */
        public function getDomains()
        {
                return array_keys($this->services);
        }

        /**
         * Get services registered for domain.
         * @param string $domain The domain name.
         * @return DirectoryService[]
         */
        public function getServices($domain)
        {
                return $this->services[$domain];
        }

        /**
         * Set default search domain.
         * @param string $domain The domain name.
         */
        public function setDomain($domain)
        {
                $this->domain = $domain;
        }

        /**
         * Get groups for user.
         * @param string $principal The user principal name.
         * @return array
         */
        public function getGroups($principal)
        {
                $domain = $this->getDomain($principal);
                $result = array();

                if (isset($this->services[$domain])) {
                        foreach ($this->services[$domain] as $services) {
                                foreach ($services as $service) {
                                        try {
                                                if (!$service->connected()) {
                                                        $service->open();
                                                }
                                                if (($groups = $service->getGroups($principal)) != null) {
                                                        $result = array_merge($result, $groups);
                                                }
                                        } catch (Exception $exception) {
                                                $this->logger->system->error($exception->getMessage());
                                        }
                                }
                        }
                }

                return $result;
        }

        /**
         * Get members of group.
         * @param string $group The group name.
         * @return array
         */
        public function getMembers($group, $domain = null)
        {
                $result = array();

                foreach ($this->services as $dom => $services) {
                        if (!isset($domain) || $dom == $domain) {
                                foreach ($services as $service) {
                                        try {
                                                if (!$service->connected()) {
                                                        $service->open();
                                                }
                                                if (($members = $service->getMembers($group)) != null) {
                                                        $result = array_merge($result, $members);
                                                }
                                        } catch (Exception $exception) {
                                                $this->logger->system->error($exception->getMessage());
                                        }
                                }
                        }
                }

                return $result;
        }

        /**
         * Get attribute (Principal::ATTR_XXX) for user.
         * 
         * <code>
         * // Get all email addresses:
         * $service->getAttribute('user@example.com', Principal::ATTR_MAIL);
         * 
         * // Get user given name:
         * $service->getAttribute('user@example.com', Principal::ATTR_GN);
         * </code>
         * 
         * @param string $principal The user principal name.
         * @param string $attribute The attribute to return.
         * @return array
         */
        public function getAttribute($principal, $attribute)
        {
                $domain = $this->getDomain($principal);
                $result = array();

                if (isset($this->services[$domain])) {
                        foreach ($this->services[$domain] as $services) {
                                foreach ($services as $service) {
                                        try {
                                                if (!$service->connected()) {
                                                        $service->open();
                                                }
                                                if (($attributes = $service->getAttribute($principal, $attribute)) != null) {
                                                        $result = array_merge($result, $attributes);
                                                }
                                        } catch (Exception $exception) {
                                                $this->logger->system->error($exception->getMessage());
                                        }
                                }
                        }
                }

                return $result;
        }

        /**
         * Get user principal object.
         * 
         * <code>
         * // Search three first Tomas in example.com domain:
         * $manager->getPrincipal('Thomas', Principal::ATTR_GN, array('domain' => 'example.com', 'limit' => 3));
         * 
         * // Get email for tomas@example.com:
         * $manager->getPrincipal('thomas@example.com', Principal::ATTR_UID, array('attr' => Principal::ATTR_MAIL));
         * </code>
         * 
         * The $options parameter is an array containing zero or more of 
         * these fields:
         * 
         * <code>
         * array(
         *       'attr'   => array(),
         *       'limit'  => 0,
         *       'domain' => null
         * )
         * </code>
         * 
         * The attr field defines which attributes to return. The limit field 
         * limits the number of returned user principal objects (use 0 for 
         * unlimited). The query can be restricted to a single domain by 
         * setting the domain field.
         * 
         * @param string $needle The attribute search string.
         * @param string $search The attribute to query.
         * @param array $options Various search options.
         * @return Principal[] Matching user principal objects.
         */
        public function getPrincipal($needle, $search = self::DEFAULT_SEARCH, $options = array(
                'attr'   => self::DEFAULT_ATTR,
                'limit'  => self::DEFAULT_LIMIT,
                'domain' => null
        ))
        {
                if (!isset($options['attr'])) {
                        $options['attr'] = self::DEFAULT_ATTR;
                }
                if (!isset($options['limit'])) {
                        $options['limit'] = self::DEFAULT_LIMIT;
                }
                if (!isset($options['domain'])) {
                        $options['domain'] = $this->domain;
                }

                $result = array();

                foreach ($this->services as $domain => $services) {
                        if (!isset($options['domain']) || $domain == $options['domain']) {
                                foreach ($services as $service) {
                                        $res = $service->getPrincipal($needle, $search, $options);
                                        if ($options['limit'] == 0) {
                                                $result = array_merge($result, $res);
                                        } elseif (count($res) + count($result) < $options['limit']) {
                                                $result = array_merge($result, $res);
                                        } else {
                                                $num = $options['limit'] - count($result);
                                                $result = array_merge($result, array_slice($res, 0, $num));
                                                return $result;
                                        }
                                }
                        }
                }

                return $result;
        }

        /**
         * Specialization of getAttribute() for email addresses.
         * @param string $principal The user principal.
         * @return array
         */
        public function getMail($principal)
        {
                return $this->getAttribute($principal, Principal::ATTR_MAIL);
        }

        /**
         * Specialization of getAttribute() for common name.
         * @param string $principal The user principal.
         * @return array
         */
        public function getName($principal)
        {
                return $this->getAttribute($principal, Principal::ATTR_MAIL);
        }

        /**
         * Get domain part from principal name.
         * @param string $principal The user principal name.
         * @return string
         */
        private function getDomain($principal)
        {
                if (($pos = strpos($principal, '@'))) {
                        return substr($principal, ++$pos);
                } else {
                        return $this->domain;   // Use default domain.
                }
        }

}