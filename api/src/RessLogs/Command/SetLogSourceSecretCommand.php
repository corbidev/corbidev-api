<?php

namespace App\RessLogs\Command;

use App\RessLogs\Entity\LogSource;
use App\RessLogs\Repository\LogSourceRepository;
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
    name: 'app:logs:set-source-secret',
    description: 'Crée ou met à jour le clientSecret d\'une LogSource (stocké en Argon2id).',
)]
final class SetLogSourceSecretCommand extends Command
{
    public function __construct(
        private readonly LogSourceRepository $logSourceRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
                ->addArgument('sourceApiKey', InputArgument::REQUIRED, 'La sourceApiKey (identifiant public) de la source à configurer.')
            ->addOption('secret', null, InputOption::VALUE_REQUIRED, 'Secret à définir (si omis, un secret aléatoire est généré et affiché une seule fois).')
                ->addOption('name', null, InputOption::VALUE_REQUIRED, 'Nom de la source si elle doit être créée (par défaut: sourceApiKey).')
                ->addOption('type', null, InputOption::VALUE_REQUIRED, 'Type de la source si elle doit être créée (par défaut: backend).')
            ->addOption('show', null, InputOption::VALUE_NONE, 'Affiche le secret généré en clair après enregistrement (à copier immédiatement, non stocké en clair).');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $sourceApiKey = (string) $input->getArgument('sourceApiKey');

        $source = $this->logSourceRepository->findOneBy(['apiKey' => $sourceApiKey]);
        $created = false;
        if (!$source instanceof LogSource) {
            /** @var string|null $nameOption */
            $nameOption = $input->getOption('name');
            /** @var string|null $typeOption */
            $typeOption = $input->getOption('type');

            $source = new LogSource();
            $source
                ->setApiKey($sourceApiKey)
                ->setName($nameOption !== null && trim($nameOption) !== '' ? trim($nameOption) : $sourceApiKey)
                ->setType($typeOption !== null && trim($typeOption) !== '' ? trim($typeOption) : 'backend')
                ->setIsActive(true)
                ->setCreatedAt(new DateTimeImmutable());

            $this->entityManager->persist($source);
            $created = true;
        }

        $io->title(sprintf('Source : %s (id: %d, type: %s, active: %s)', $source->getName(), $source->getId(), $source->getType(), $source->isActive() ? 'oui' : 'non'));

        /** @var string|null $secret */
        $secret = $input->getOption('secret');

        if ($secret === null) {
            if ($input->isInteractive()) {
                $helper = $this->getHelper('question');
                $question = new Question('<info>Secret à définir</info> (laisser vide pour générer automatiquement) : ');
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
            $io->error('Le secret doit comporter au moins 16 caractères.');

            return Command::FAILURE;
        }

        $hash = password_hash($secret, PASSWORD_ARGON2ID);
        $source->setClientSecret($hash);
        $this->entityManager->flush();

        $io->success($created ? 'Source créée et clientSecret défini avec succès.' : 'clientSecret mis à jour avec succès.');

        if ($generated || $input->getOption('show')) {
            $io->caution('Secret en clair (à copier immédiatement, il ne sera plus accessible) :');
            $io->writeln('  <fg=yellow>' . $secret . '</>');
        }

        return Command::SUCCESS;
    }
}
