<?php

declare(strict_types=1);
namespace glyphic\models;
use \glyphic\PDOQueryController;
use \glyphic\QueryBuilder;
use \glyphic\UniqueId;
use \glyphic\TimeStamp;
use \core\exceptions\RecordNotFoundException;
use \core\exceptions\ResourceAccessForbiddenException;

class Tenant {

    private $tenantId;
    private $publicKey;
    private $privateKey;
    private $doExist;
    private $status;

    public function __construct(
        string $publicKey = null
        )
    {
        if (null!==$publicKey) {

            $tenant = $this->fetch($publicKey);

            # Tenant primary validation
            if (!$tenant['hasRecord']) {
                throw new RecordNotFoundException(
                    "Tenant public key: {$publicKey} do not exist in the database"
                );
            }

            foreach ($tenant as $key => $value) {
                $this->$key = $value;
            }

        }

    }

    public function create()
    {

        $this->tenantId = UniqueId::create32bitKey(UniqueId::BETANUMERIC);
        $this->publicKey = UniqueId::create32bitKey(UniqueId::ALPHANUMERIC);
        $this->privateKey = UniqueId::create32bitKey(UniqueId::ALPHANUMERIC);

        $query = new PDOQueryController(
            (new QueryBuilder('/tenants/create/tenant'))->build()
        );
        $query->prepare([
            ':tenantId' => $this->tenantId,
            ':publicKey' => $this->publicKey,
            ':privateKey' => $this->privateKey,
            ':createdAt' => TimeStamp::now(),
            ':podId' => 1,
            ':domain' => 'localhost',
            ':status' => 'ACTIVE'
        ]);
        $query->post();
    }

    public function doExist()
    {
        return $this->doExist;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function getTenantId()
    {
        return $this->tenantId;
    }

    public function getPublicKey()
    {
        return $this->publicKey;
    }

    public function getPrivateKey()
    {
        return $this->privateKey;
    }

    public function fetch(
        string $publicKey
        )
    {
        $query = new PDOQueryController(
            (new QueryBuilder('/tenants/get/tenant'))->build()
        );
        $query->prepare([':publicKey'=>$publicKey]);
        return $query->get();

    }

}
