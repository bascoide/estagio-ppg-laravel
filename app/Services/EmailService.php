<?php

namespace App\Services;

use App\Models\FinalDocument;
use App\Models\PresidentValidatedDocument;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

class EmailService
{
    private array $config;

    public function __construct()
    {
        $this->config = [
            'host'       => config('mail.mailers.smtp.host'),
            'port'       => config('mail.mailers.smtp.port'),
            'username'   => config('mail.mailers.smtp.username'),
            'password'   => config('mail.mailers.smtp.password'),
            'encryption' => config('mail.mailers.smtp.encryption'),
            'from_email' => config('mail.from.address'),
            'from_name'  => config('mail.from.name'),
            'timeout'    => 30,
        ];
    }

    private function send(
        string $to,
        string $subject,
        string $message,
        bool $isHtml = true,
        array $attachments = [],
        ?string $replyTo = null
    ): bool {
        $mail = new PHPMailer(true);

        try {
            $mail->CharSet = 'UTF-8';
            $mail->isSMTP();
            $mail->Host       = $this->config['host'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $this->config['username'];
            $mail->Password   = $this->config['password'];
            $mail->SMTPSecure = $this->config['encryption'];
            $mail->Port       = $this->config['port'];
            $mail->Timeout    = $this->config['timeout'];

            $mail->setFrom($this->config['from_email'], $this->config['from_name']);
            $mail->addAddress($to);

            if ($replyTo) {
                $mail->addReplyTo($replyTo);
            }

            foreach ($attachments as $filePath) {
                $mail->addAttachment($filePath);
            }

            $mail->isHTML($isHtml);
            $mail->Subject = $subject;
            $mail->Body    = $message;

            if (!$isHtml) {
                $mail->AltBody = strip_tags($message);
            }

            return $mail->send();
        } catch (PHPMailerException $e) {
            \Log::error('Email sending failed: ' . $e->getMessage());
            return false;
        }
    }

    private function renderEmailTemplate(
        string $eyebrow,
        string $title,
        string $contentHtml,
        ?string $buttonLabel = null,
        ?string $buttonUrl = null,
        string $statusLabel = 'Informacao',
        string $statusTone = 'blue'
    ): string {
        $tones = [
            'blue' => [
                'badgeColor' => '#1d4ed8',
                'buttonBackground' => '#1e3a8a',
            ],
            'green' => [
                'badgeColor' => '#15803d',
                'buttonBackground' => '#1e3a8a',
            ],
            'red' => [
                'badgeColor' => '#b91c1c',
                'buttonBackground' => '#1e3a8a',
            ],
            'gray' => [
                'badgeColor' => '#374151',
                'buttonBackground' => '#1e3a8a',
            ],
        ];

        $palette = $tones[$statusTone] ?? $tones['blue'];
        $buttonHtml = '';

        if ($buttonLabel && $buttonUrl) {
            $buttonHtml = '
                <tr>
                    <td style="padding: 8px 32px 0 32px;">
                        <a href="' . htmlspecialchars($buttonUrl) . '" style="display: inline-block; background-color: ' . $palette['buttonBackground'] . '; color: #ffffff; text-decoration: none; padding: 12px 22px; border-radius: 6px; font-weight: 600;">' . htmlspecialchars($buttonLabel) . '</a>
                    </td>
                </tr>';
        }

        return '
            <!DOCTYPE html>
            <html lang="pt">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>' . htmlspecialchars($title) . '</title>
            </head>
            <body style="margin: 0; padding: 0; background-color: #f3f4f6; font-family: Arial, Helvetica, sans-serif; color: #1f2937;">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #f3f4f6; padding: 32px 16px;">
                    <tr>
                        <td align="center">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width: 640px; background-color: #ffffff; border: 1px solid #e5e7eb;">
                                <tr>
                                    <td style="background-color: #e5e7eb; padding: 18px 32px; border-bottom: 1px solid #d1d5db;">
                                        <div style="font-size: 20px; font-weight: 700; color: #111827;">PPG</div>
                                        <div style="margin-top: 4px; font-size: 13px; color: #4b5563;">' . htmlspecialchars($eyebrow) . ' | ISCAP</div>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 32px 32px 8px 32px;">
                                        <div style="font-size: 12px; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase; color: ' . $palette['badgeColor'] . ';">' . htmlspecialchars($statusLabel) . '</div>
                                        <h1 style="margin: 10px 0 12px 0; font-size: 28px; line-height: 1.2; color: #111827;">' . htmlspecialchars($title) . '</h1>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 0 32px; font-size: 15px; line-height: 1.7; color: #374151;">
                                        ' . $contentHtml . '
                                    </td>
                                </tr>
                                ' . $buttonHtml . '
                                <tr>
                                    <td style="padding: 24px 32px 32px 32px; font-size: 14px; line-height: 1.7; color: #4b5563;">
                                        <p style="margin: 0;">Saudacoes academicas,</p>
                                        <p style="margin: 4px 0 0 0;"><strong>Equipa de Atendimento</strong><br>ISCAP</p>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="background-color: #f3f4f6; border-top: 1px solid #e5e7eb; padding: 16px 32px; font-size: 12px; color: #6b7280; text-align: center;">
                                        PPG © 2025 - ISCAP. Todos os direitos reservados.
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </body>
            </html>';
    }

    private function renderRejectedFields(array $rejectedFields): string
    {
        if (empty($rejectedFields)) {
            return '';
        }

        $items = '';
        foreach ($rejectedFields as $name => $value) {
            $items .= '<li style="margin-bottom: 6px;"><strong>' . htmlspecialchars($name) . ':</strong> ' . htmlspecialchars($value) . '</li>';
        }

        return '
            <div style="margin: 16px 0; padding: 16px; background-color: #f9fafb; border: 1px solid #e5e7eb;">
                <p style="margin: 0 0 10px 0;"><strong>Campos rejeitados:</strong></p>
                <ul style="margin: 0; padding-left: 20px;">' . $items . '</ul>
            </div>';
    }

    public function sendConfirmationCode(string $userEmail, string $verificationCode): bool
    {
        $fullUrl = url('/user-verification') . '?email=' . urlencode($userEmail) . '&verification_code=' . urlencode($verificationCode);

        $html = $this->renderEmailTemplate(
            'Portal de Estagios',
            'Verificacao da sua conta',
            '<p style="margin: 0 0 14px 0;">Para concluir o registo, confirme a sua conta atraves do link abaixo.</p>
             <p style="margin: 0;">Se nao solicitou este registo, pode ignorar esta mensagem.</p>',
            'Confirmar conta',
            $fullUrl,
            'Verificacao',
            'blue'
        );

        return $this->send($userEmail, 'Verificação da sua Conta', $html, true, [], $this->config['from_email']);
    }

    public function sendAcceptedEmail(string $userEmail, int $finalDocumentId): bool
    {
        $document = FinalDocument::find($finalDocumentId);
        if (!$document) return false;

        $pdfPath = public_path('uploads/generated_docs/' . $document->pdf_path);
        if (!file_exists($pdfPath)) return false;

        $fullUrl = url('/user-upload-final-document-form') . '?final_document_id=' . $finalDocumentId;

        $html = $this->renderEmailTemplate(
            'Gestao de Protocolos',
            'Protocolo aprovado',
            '<p style="margin: 0 0 14px 0;">O seu protocolo foi <strong>aprovado</strong> e processado com sucesso.</p>
             <p style="margin: 0 0 14px 0;">Solicite agora as assinaturas necessarias para a finalizacao do documento.</p>
             <p style="margin: 0;">O ficheiro segue em anexo para referencia.</p>',
            'Submeter documento assinado',
            $fullUrl,
            'Aprovado',
            'green'
        );

        return $this->send($userEmail, 'O Seu Protocolo foi Aprovado', $html, true, [$pdfPath], $this->config['from_email']);
    }

    public function sendRejectedEmail(string $userEmail, int $finalDocumentId, string $rejectionReason = '', array $rejectedFields = []): bool
    {
        $document = FinalDocument::find($finalDocumentId);
        if (!$document) return false;

        $pdfPath = public_path('uploads/generated_docs/' . $document->pdf_path);
        if (!file_exists($pdfPath)) return false;

        $reasonHtml = !empty($rejectionReason)
            ? '<p style="margin: 0 0 14px 0;"><strong>Motivo:</strong> ' . htmlspecialchars($rejectionReason) . '</p>'
            : '';

        $html = $this->renderEmailTemplate(
            'Gestao de Protocolos',
            'Protocolo rejeitado',
            '<p style="margin: 0 0 14px 0;">O seu protocolo foi <strong>rejeitado</strong>.</p>' .
            $reasonHtml .
            $this->renderRejectedFields($rejectedFields) .
            '<p style="margin: 14px 0 0 0;">O documento segue em anexo para sua referencia.</p>',
            null,
            null,
            'Rejeitado',
            'red'
        );

        return $this->send($userEmail, 'O Seu Protocolo foi Rejeitado', $html, true, [$pdfPath], $this->config['from_email']);
    }

    public function sendRejectedValidationEmail(string $userEmail, int $finalDocumentId, string $rejectionReason = ''): bool
    {
        $document = FinalDocument::find($finalDocumentId);
        if (!$document) return false;

        $pdfPath = public_path('uploads/generated_docs/' . $document->pdf_path);
        if (!file_exists($pdfPath)) return false;

        $html = $this->renderEmailTemplate(
            'Gestao de Protocolos',
            'Protocolo invalidado',
            '<p style="margin: 0 0 14px 0;">O seu protocolo foi <strong>invalidado</strong>.</p>' .
            (!empty($rejectionReason) ? '<p style="margin: 0 0 14px 0;"><strong>Motivo:</strong> ' . htmlspecialchars($rejectionReason) . '</p>' : '') .
            '<p style="margin: 0;">Consulte o email enviado anteriormente para rever o processo.</p>',
            null,
            null,
            'Invalidado',
            'red'
        );

        return $this->send($userEmail, 'O Seu Protocolo foi Invalidado', $html, true, [$pdfPath], $this->config['from_email']);
    }

    public function sendPresidentialValidationEmail(string $presidentEmail, int $finalDocumentId, string $adminName): bool
    {
        $document = FinalDocument::find($finalDocumentId);
        if (!$document) return false;

        $pdfPath = public_path('uploads/generated_docs/' . $document->pdf_path);
        if (!file_exists($pdfPath)) return false;

        $presidentDoc = PresidentValidatedDocument::where('final_document_id', $finalDocumentId)->first();
        $uuid = $presidentDoc ? $presidentDoc->uuid : '';
        $fullUrl = url('/president-upload-final-document-form') . '?uuid=' . $uuid;

        $html = $this->renderEmailTemplate(
            'Validacao Presidencial',
            'Assinatura pendente do protocolo',
            '<p style="margin: 0 0 14px 0;">Em anexo encontra o protocolo para assinatura.</p>
             <p style="margin: 0 0 14px 0;">Depois de assinar, submeta o documento final atraves do botao abaixo.</p>
             <p style="margin: 0;"><strong>Responsavel pelo envio:</strong> ' . htmlspecialchars($adminName) . '</p>',
            'Submeter protocolo assinado',
            $fullUrl,
            'Pendente',
            'blue'
        );

        return $this->send($presidentEmail, 'O Protocolo está à espera da sua assinatura', $html, true, [$pdfPath], $this->config['from_email']);
    }

    public function sendAcceptedValidationEmail(string $userEmail, int $finalDocumentId): bool
    {
        $document = FinalDocument::find($finalDocumentId);
        if (!$document) return false;

        $pdfPath = public_path('uploads/generated_docs/' . $document->pdf_path);
        if (!file_exists($pdfPath)) return false;

        $html = $this->renderEmailTemplate(
            'Gestao de Protocolos',
            'Protocolo validado',
            '<p style="margin: 0 0 14px 0;">O seu protocolo foi <strong>aprovado</strong> e finalizado com sucesso.</p>
             <p style="margin: 0;">O documento final segue em anexo para consulta.</p>',
            null,
            null,
            'Validado',
            'green'
        );

        return $this->send($userEmail, 'O Seu Protocolo foi Validado', $html, true, [$pdfPath], $this->config['from_email']);
    }

    public function sendCancelledEmail(string $userEmail, int $finalDocumentId): bool
    {
        $document = FinalDocument::find($finalDocumentId);
        if (!$document) return false;

        $pdfPath = public_path('uploads/generated_docs/' . $document->pdf_path);
        if (!file_exists($pdfPath)) return false;

        $html = $this->renderEmailTemplate(
            'Gestao de Protocolos',
            'Protocolo anulado',
            '<p style="margin: 0 0 14px 0;">O seu protocolo foi <strong>anulado</strong>.</p>
             <p style="margin: 0;">Esta acao foi registada conforme solicitado.</p>',
            null,
            null,
            'Anulado',
            'gray'
        );

        return $this->send($userEmail, 'O Seu Protocolo foi anulado', $html, true, [$pdfPath], $this->config['from_email']);
    }

    public function sendPlanEmail(string $userEmail, int $finalDocumentId): bool
    {
        $document = FinalDocument::find($finalDocumentId);
        if (!$document) return false;

        $pdfPath = public_path('uploads/generated_docs/' . $document->pdf_path);
        if (!file_exists($pdfPath)) return false;

        $fullUrl = url('/form') . '?filled_plan_id=' . $finalDocumentId;

        $html = $this->renderEmailTemplate(
            'Gestao de Planos',
            'Plano pendente',
            '<p style="margin: 0 0 14px 0;">O seu plano encontra-se <strong>pendente</strong>.</p>
             <p style="margin: 0 0 14px 0;">Solicite as assinaturas necessarias para finalizar o documento.</p>
             <p style="margin: 0;">O ficheiro segue em anexo para referencia.</p>',
            'Submeter plano assinado',
            $fullUrl,
            'Pendente',
            'blue'
        );

        return $this->send($userEmail, 'O Seu Plano está Pendente', $html, true, [$pdfPath], $this->config['from_email']);
    }
}
