<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

#[AsCommand(
    name: 'app:test-mail',
    description: 'Send a test email using environment configuration'
)]
class TestMailCommand extends Command
{
    public function __construct(
        private MailerInterface $mailer,
        #[Autowire('%env(MAIL_FROM)%')]
        private string $mailFrom,
        #[Autowire('%env(MAIL_TO)%')]
        private string $mailTo
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->log($output, ['START', 'test-mail']);

        try {
            $email = (new Email())
                ->from($this->mailFrom)
                ->to($this->mailTo)
                ->subject('Test mail Symfony')
                ->text('Ça fonctionne 🎉');

            $this->mailer->send($email);

            $this->log($output, ['SUCCESS', "Mail sent to {$this->mailTo}"]);

            return Command::SUCCESS;

        } catch (\Throwable $e) {

            $this->log($output, ['ERROR', $e->getMessage()]);

            return Command::FAILURE;
        }
    }

    /**
     * 📊 Logger CSV cohérent avec ton système
     */
    private function log(OutputInterface $output, array $fields): void
    {
        $line = array_merge(
            [(new \DateTime())->format('Y-m-d H:i:s')],
            $fields
        );

        $output->writeln(implode('|', $line));
    }
}