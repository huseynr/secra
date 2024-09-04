<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\VacationRental;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'app:import-vacation-rentals',
    description: 'Imports vacation rentals from a CSV file',
)]
class ImportVacationRentalsCommand extends Command
{
    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;
    private ValidatorInterface $validator;

    public function __construct(
        EntityManagerInterface $entityManager,
        LoggerInterface $logger,
        ValidatorInterface $validator
    ) {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->validator = $validator;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('filePath', InputArgument::REQUIRED, 'Path to the CSV file');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $filePath = $input->getArgument('filePath');

        if (!file_exists($filePath) || !is_readable($filePath)) {
            $this->logger->error("CSV file not found or is not readable: $filePath");
            $io->error("CSV file not found or is not readable.");
            return Command::FAILURE;
        }

        $io->progressStart();
        $this->logger->info("Starting import from CSV file: $filePath");

        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            $this->logger->error("Failed to open CSV file: $filePath");
            $io->error("Failed to open CSV file.");
            return Command::FAILURE;
        }

        $header = fgetcsv($handle);
        if ($header === false) {
            $this->logger->error("CSV file is empty or has no valid header.");
            $io->error("CSV file is empty or has no valid header.");
            fclose($handle);
            return Command::FAILURE;
        }

        $rowCount = 0;
        try {
            while (($row = fgetcsv($handle)) !== false) {
                $data = array_combine($header, $row);

                if ($data === false) {
                    $this->logger->warning("Skipping malformed CSV row: " . json_encode($row));
                    continue;
                }

                $rental = new VacationRental();
                $rental->setName($data['name'] ?? '');
                $rental->setDescription($data['description'] ?? null);
                $rental->setPrice((float)($data['price'] ?? 0));
                $rental->setLocation($data['location'] ?? '');

                $errors = $this->validator->validate($rental);
                if (count($errors) > 0) {
                    $this->logger->error("Validation errors: " . (string)$errors);
                    continue;
                }

                $this->entityManager->persist($rental);

                if (++$rowCount % 20 === 0) {
                    $this->entityManager->flush();
                    $this->entityManager->clear();
                }

                $io->progressAdvance();
            }

            $this->entityManager->flush();
            $io->progressFinish();
            $io->success("CSV import completed successfully.");

            fclose($handle);
            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->logger->error("Error during CSV import: " . $e->getMessage());
            $io->error("An error occurred during the import.");
            fclose($handle);
            return Command::FAILURE;
        }
    }
}
