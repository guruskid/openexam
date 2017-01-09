<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    DirectoryCache.php
// Created: 2017-01-09 05:18:23
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Catalog;

use Phalcon\Cache\BackendInterface;
use Phalcon\Mvc\User\Component;

/**
 * Cache for the directory service.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class DirectoryCache extends Component implements DirectoryService
{

        /**
         * The cache backend.
         * @var BackendInterface 
         */
        private $_cache;

        /**
         * Constructor.
         * @param BackendInterface $cache The cache backend.
         */
        public function __construct($cache = null)
        {
                if (isset($cache)) {
                        $this->_cache = $cache;
                } else {
                        $this->_cache = $this->cache;
                }
        }

        /**
         * Set cache backend.
         * @param BackendInterface $cache The cache backend.
         */
        public function setBackend($cache)
        {
                $this->_cache = $cache;
        }

        public function getConnection()
        {
                return null;
        }

        public function getDomains()
        {
                return null;
        }

        public function getName()
        {
                return 'cache';
        }

        public function getAttribute($principal, $attribute)
        {
                $cachekey = sprintf("catalog-%s-attribute-%s-%s", $this->getName(), $attribute, md5($principal));
                return $this->_cache->get($cachekey);
        }

        public function setAttribute($principal, $attribute, &$content)
        {
                $cachekey = sprintf("catalog-%s-attribute-%s-%s", $this->getName(), $attribute, md5($principal));
                $this->_cache->save($cachekey, $content);
        }

        public function getGroups($principal, $attributes)
        {
                $cachekey = sprintf("catalog-%s-groups-%s", $this->getName(), md5(serialize(array($principal, $attributes))));
                return $this->_cache->get($cachekey);
        }

        public function setGroups($principal, $attributes, &$content)
        {
                $cachekey = sprintf("catalog-%s-groups-%s", $this->getName(), md5(serialize(array($principal, $attributes))));
                $this->_cache->save($cachekey, $content);
        }

        public function getMembers($group, $domain, $attributes)
        {
                $cachekey = sprintf("catalog-%s-members-%s", $this->getName(), md5(serialize(array($group, $domain, $attributes))));
                return $this->_cache->get($cachekey);
        }

        public function setMembers($group, $domain, $attributes, &$content)
        {
                $cachekey = sprintf("catalog-%s-members-%s", $this->getName(), md5(serialize(array($group, $domain, $attributes))));
                $this->_cache->save($cachekey, $content);
        }

        public function getPrincipal($needle, $search, $options)
        {
                $cachekey = sprintf("catalog-%s-principal-%s-%s", $this->getName(), $search, md5(serialize(array($needle, $options))));
                return $this->_cache->get($cachekey);
        }

        public function setPrincipal($needle, $search, $options, &$content)
        {
                $cachekey = sprintf("catalog-%s-principal-%s-%s", $this->getName(), $search, md5(serialize(array($needle, $options))));
                $this->_cache->save($cachekey, $content);
        }

}
