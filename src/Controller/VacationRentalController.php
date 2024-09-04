<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\VacationRental;
use App\Repository\VacationRentalRepository;
use App\Service\VacationRentalService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

#[Route('/api/v1/vacation-rentals', name: 'vacation_rental_')]
class VacationRentalController extends AbstractController
{
    private VacationRentalService $rentalService;

    public function __construct(VacationRentalService $rentalService)
    {
        $this->rentalService = $rentalService;
    }

    #[Route('/', methods: ['GET'], name: 'index')]
    public function index(VacationRentalRepository $repository): JsonResponse
    {
        $rentals = $repository->findAll();

        if (empty($rentals)) {
            return $this->json(['message' => 'No vacation rentals found'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($rentals, Response::HTTP_OK);
    }

    #[Route('/{id}', methods: ['GET'], name: 'show')]
    public function show(int $id, VacationRentalRepository $repository): JsonResponse
    {
        $rental = $repository->find($id);

        if (!$rental) {
            throw new NotFoundHttpException('Vacation rental not found');
        }

        return $this->json($rental, Response::HTTP_OK);
    }

    #[Route('/', methods: ['POST'], name: 'create')]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return $this->json(['message' => 'Invalid input data'], Response::HTTP_BAD_REQUEST);
        }

        $rental = $this->rentalService->createVacationRental($data);

        if ($rental === null) {
            return $this->json(['message' => 'Failed to create vacation rental'], Response::HTTP_BAD_REQUEST);
        }

        return $this->json($rental, Response::HTTP_CREATED);
    }

    #[Route('/{id}', methods: ['PUT'], name: 'update')]
    public function update(int $id, Request $request, VacationRentalRepository $repository): JsonResponse
    {
        $rental = $repository->find($id);

        if (!$rental) {
            throw new NotFoundHttpException('Vacation rental not found');
        }

        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return $this->json(['message' => 'Invalid input data'], Response::HTTP_BAD_REQUEST);
        }

        $updatedRental = $this->rentalService->updateVacationRental($rental, $data);

        if ($updatedRental === null) {
            return $this->json(['message' => 'Failed to update vacation rental'], Response::HTTP_BAD_REQUEST);
        }

        return $this->json($updatedRental, Response::HTTP_OK);
    }

    #[Route('/{id}', methods: ['DELETE'], name: 'delete')]
    public function delete(int $id, VacationRentalRepository $repository): JsonResponse
    {
        $rental = $repository->find($id);

        if (!$rental) {
            throw new NotFoundHttpException('Vacation rental not found');
        }

        $isDeleted = $this->rentalService->deleteVacationRental($rental);

        if (!$isDeleted) {
            return $this->json(['message' => 'Failed to delete vacation rental'], Response::HTTP_BAD_REQUEST);
        }

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
