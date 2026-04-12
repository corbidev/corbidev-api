<?php

namespace App\RessAuth\Command;

use App\RessAuth\Entity\AuthCredential;
use App\RessAuth\RessAuthConstants;
use App\RessAuth\Repository\AuthCredentialRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: RessAuthConstants::COMMAND_NAME,
    description: RessAuthConstants::COMMAND_DESCRIPTION,
)]
final class SetLogSourceSecretCommand extends Command
{
    public function __construct(
        private readonly AuthCredentialRepository $authCredentialRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument(RessAuthConstants::ARG_SOURCE_API_KEY, InputArgument::REQUIRED, RessAuthConstants::ARG_SOURCE_API_KEY_DESCRIPTION)
            ->addOption(RessAuthConstants::OPTION_SECRET, null, InputOption::VALUE_REQUIRED, RessAuthConstants::OPTION_SECRET_DESCRIPTION)
            ->addOption(RessAuthConstants::OPTION_NAME, null, InputOption::VALUE_REQUIRED, RessAuthConstants::OPTION_NAME_DESCRIPTION)
            ->addOption(RessAuthConstants::OPTION_TYPE, null, InputOption::VALUE_REQUIRED, RessAuthConstants::OPTION_TYPE_DESCRIPTION)
            ->addOption(RessAuthConstants::OPTION_SHOW, null, InputOption::VALUE_NONE, RessAuthConstants::OPTION_SHOW_DESCRIPTION);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $sourceApiKey = (string) $input->getArgument(RessAuthConstants::ARG_SOURCE_API_KEY);

        $source = $this->authCredentialRepository->findOneBy(['apiKey' => $sourceApiKey]);
        $created = false;
        if (!$source instanceof AuthCredential) {
            /** @var string|null $nameOption */
            $nameOption = $input->getOption(RessAuthConstants::OPTION_NAME);
            /** @var string|null $typeOption */
            $typeOption = $input->getOption(RessAuthConstants::OPTION_TYPE);

            $source = new AuthCredential();
            $source
                ->setApiKey($sourceApiKey)
                ->setName($nameOption !== null && trim($nameOption) !== '' ? trim($nameOption) : $sourceApiKey)
                ->setType($typeOption !== null && trim($typeOption) !== '' ? trim($typeOption) : RessAuthConstants::SOURCE_TYPE_BACKEND)
                ->setIsActive(true)
                ->setCreatedAt(new DateTimeImmutable())
                ->setUpdatedAt(new DateTimeImmutable());

            $this->entityManager->persist($source);
            $created = true;
        }

        $sourceIdentifier = $source->getId() ?? RessAuthConstants::SOURCE_IDENTIFIER_NEW;
        $io->title(sprintf(RessAuthConstants::TITLE_SOURCE, $source->getName(), (string) $sourceIdentifier, $source->getType(), $source->isActive() ? RessAuthConstants::BOOLEAN_YES : RessAuthConstants::BOOLEAN_NO));

        /** @var string|null $secret */
        $secret = $input->getOption(RessAuthConstants::OPTION_SECRET);

        if ($secret === null) {
            if ($input->isInteractive()) {
                $helper = $this->getHelper(RessAuthConstants::CONSOLE_HELPER_QUESTION);
            $question = new Question(RessAuthConstants::QUESTION_SECRET);
                $question->setHidden(true);
                $question->setHiddenFallback(false);
                /** @var string|null $answer */
                $answer = $helper->ask($input, $output, $question);
                $secret = ($answer !== null && trim($answer) !== '') ? $answer : null;
            }
        }

        $generated = false;
        if ($secret === null || trim($secret) === '') {
            $secret = bin2hex(random_bytes(32));
            $generated = true;
        }

        if (strlen($secret) < 16) {
            $io->error(RessAuthConstants::ERROR_SECRET_TOO_SHORT);

            return Command::FAILURE;
        }

        $hash = password_hash($secret, PASSWORD_ARGON2ID);

        $source
            ->setClientSecretHash($hash)
            ->setUpdatedAt(new DateTimeImmutable());

        $this->entityManager->flush();

        $io->success($created ? RessAuthConstants::SUCCESS_SOURCE_CREATED : RessAuthConstants::SUCCESS_SOURCE_UPDATED);

        if ($generated || $input->getOption(RessAuthConstants::OPTION_SHOW)) {
            $io->caution(RessAuthConstants::CAUTION_SECRET_CLEAR);
            $io->writeln(sprintf(RessAuthConstants::SECRET_OUTPUT_FORMAT, $secret));
        }

        return Command::SUCCESS;
    }
}