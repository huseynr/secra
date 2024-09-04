<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\VacationRental;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class VacationRentalService
{
    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;

    public function __construct(EntityManagerInterface $entityManager, LoggerInterface $logger)
    {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    public function createVacationRental(array $data): ?VacationRental
    {
        $rental = new VacationRental();
        $rental->setName($data['name'] ?? '');
        $rental->setDescription($data['description'] ?? null);
        $rental->setPrice((float)($data['price'] ?? 0));
        $rental->setLocation($data['location'] ?? '');

        try {
            $this->entityManager->persist($rental);
            $this->entityManager->flush();

            return $rental;
        } catch (\Throwable $e) {
            $this->logger->error('Error while creating vacation rental: ' . $e->getMessage());
            return null;
        }
    }

    public function updateVacationRental(VacationRental $rental, array $data): ?VacationRental
    {
        $rental->setName($data['name'] ?? $rental->getName());
        $rental->setDescription($data['description'] ?? $rental->getDescription());
        $rental->setPrice((float)($data['price'] ?? $rental->getPrice()));
        $rental->setLocation($data['location'] ?? $rental->getLocation());

        try {
            $this->entityManager->flush();

            return $rental;
        } catch (\Throwable $e) {
            $this->logger->error('Error while updating vacation rental: ' . $e->getMessage());
            return null;
        }
    }

    public function deleteVacationRental(VacationRental $rental): bool
    {
        try {
            $this->entityManager->remove($rental);
            $this->entityManager->flush();
            
            return true;
        } catch (\Throwable $e) {
            $this->logger->error('Error while deleting vacation rental: ' . $e->getMessage());
            return false;
        }
    }
}
