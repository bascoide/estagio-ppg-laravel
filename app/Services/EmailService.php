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

    public function sendConfirmationCode(string $userEmail, string $verificationCode): bool
    {
        $fullUrl = url('/user-verification') . '?email=' . urlencode($userEmail) . '&verification_code=' . urlencode($verificationCode);

        $html = "
            <html><head><meta charset='UTF-8'><title>Verificação de Conta</title></head>
            <body>
                <h2>Verifique a sua conta!</h2>
                <p>Para concluir a verificação da sua conta, clique no link abaixo:</p>
                <p><a href='" . htmlspecialchars($fullUrl) . "'>Clique aqui para verificar</a></p>
                <p>Se não solicitou este cadastro, desconsidere esta mensagem.</p>
                <br><p>Saudações académicas,</p><p><strong>Equipa de Atendimento</strong><br>ISCAP</p>
            </body></html>
        ";

        return $this->send($userEmail, 'Verificação da sua Conta', $html, true, [], $this->config['from_email']);
    }

    public function sendAcceptedEmail(string $userEmail, int $finalDocumentId): bool
    {
        $document = FinalDocument::find($finalDocumentId);
        if (!$document) return false;

        $pdfPath = public_path('uploads/generated_docs/' . $document->pdf_path);
        if (!file_exists($pdfPath)) return false;

        $fullUrl = url('/user-upload-final-document-form') . '?final_document_id=' . $finalDocumentId;

        $html = "
            <html><head><meta charset='UTF-8'><title>Protocolo Aprovado</title></head>
            <body>
                <h2>Protocolo Aprovado</h2>
                <p>O protocolo foi <strong>aprovado</strong> e processado com sucesso.</p>
                <p>Por favor, solicite as assinaturas necessárias para a finalização do mesmo.</p>
                <p>O documento encontra-se em anexo a este e-mail.</p>
                <p>Submeta o seu documento para assinatura: <a href='" . htmlspecialchars($fullUrl) . "'>Clique aqui</a></p>
                <br><p>Saudações académicas,</p><p><strong>Equipa de Atendimento</strong><br>ISCAP</p>
            </body></html>
        ";

        return $this->send($userEmail, 'O Seu Protocolo foi Aprovado', $html, true, [$pdfPath], $this->config['from_email']);
    }

    public function sendRejectedEmail(string $userEmail, int $finalDocumentId, string $rejectionReason = '', array $rejectedFields = []): bool
    {
        $document = FinalDocument::find($finalDocumentId);
        if (!$document) return false;

        $pdfPath = public_path('uploads/generated_docs/' . $document->pdf_path);
        if (!file_exists($pdfPath)) return false;

        $fieldsHtml = '';
        if (!empty($rejectedFields)) {
            $fieldsHtml = '<p><strong>Campos rejeitados:</strong></p><ul>';
            foreach ($rejectedFields as $name => $value) {
                $fieldsHtml .= '<li>' . htmlspecialchars($name) . ': ' . htmlspecialchars($value) . '</li>';
            }
            $fieldsHtml .= '</ul>';
        }

        $html = "
            <html><head><meta charset='UTF-8'><title>Protocolo Rejeitado</title></head>
            <body>
                <h2>Protocolo Rejeitado</h2>
                <p>O seu protocolo foi <strong>rejeitado</strong>.</p>
                " . (!empty($rejectionReason) ? "<p><strong>Motivo:</strong> " . htmlspecialchars($rejectionReason) . "</p>" : "") . "
                $fieldsHtml
                <p>O documento está anexado para sua referência.</p>
                <br><p>Saudações académicas,</p><p><strong>Equipa de Atendimento</strong><br>ISCAP</p>
            </body></html>
        ";

        return $this->send($userEmail, 'O Seu Protocolo foi Rejeitado', $html, true, [$pdfPath], $this->config['from_email']);
    }

    public function sendRejectedValidationEmail(string $userEmail, int $finalDocumentId, string $rejectionReason = ''): bool
    {
        $document = FinalDocument::find($finalDocumentId);
        if (!$document) return false;

        $pdfPath = public_path('uploads/generated_docs/' . $document->pdf_path);
        if (!file_exists($pdfPath)) return false;

        $html = "
            <html><head><meta charset='UTF-8'><title>Protocolo Invalidado</title></head>
            <body>
                <h2>Protocolo Invalidado</h2>
                <p>O seu protocolo foi <strong>rejeitado</strong>.</p>
                " . (!empty($rejectionReason) ? "<p><strong>Motivo:</strong> " . htmlspecialchars($rejectionReason) . "</p>" : "") . "
                <p>Por favor, consulte o e-mail enviado anteriormente.</p>
                <br><p>Saudações académicas,</p><p><strong>Equipa de Atendimento</strong><br>ISCAP</p>
            </body></html>
        ";

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

        $html = "
            <html><head><meta charset='UTF-8'><title>Protocolo em Espera</title></head>
            <body>
                <h2>Deve por favor assinar este protocolo</h2>
                <p>Em anexo encontra um protocolo, para por favor, assinar.</p>
                <p>Submeta-o assinado através do seguinte link:</p>
                <p><a href='" . htmlspecialchars($fullUrl) . "'>Clique aqui</a></p>
                <br><p>Saudações académicas,</p><p><strong>Equipa de Atendimento</strong><br>ISCAP</p>
                <p>" . htmlspecialchars($adminName) . "</p>
            </body></html>
        ";

        return $this->send($presidentEmail, 'O Protocolo está à espera da sua assinatura', $html, true, [$pdfPath], $this->config['from_email']);
    }

    public function sendAcceptedValidationEmail(string $userEmail, int $finalDocumentId): bool
    {
        $document = FinalDocument::find($finalDocumentId);
        if (!$document) return false;

        $pdfPath = public_path('uploads/generated_docs/' . $document->pdf_path);
        if (!file_exists($pdfPath)) return false;

        $html = "
            <html><head><meta charset='UTF-8'><title>Protocolo Validado</title></head>
            <body>
                <h2>Protocolo Validado</h2>
                <p>O seu protocolo foi <strong>aprovado</strong> e finalizado com sucesso.</p>
                <p>Poderá encontrar o documento finalizado em anexo a este e-mail.</p>
                <br><p>Saudações académicas,</p><p><strong>Equipa de Atendimento</strong><br>ISCAP</p>
            </body></html>
        ";

        return $this->send($userEmail, 'O Seu Protocolo foi Validado', $html, true, [$pdfPath], $this->config['from_email']);
    }

    public function sendCancelledEmail(string $userEmail, int $finalDocumentId): bool
    {
        $document = FinalDocument::find($finalDocumentId);
        if (!$document) return false;

        $pdfPath = public_path('uploads/generated_docs/' . $document->pdf_path);
        if (!file_exists($pdfPath)) return false;

        $html = "
            <html><head><meta charset='UTF-8'><title>Protocolo Anulado</title></head>
            <body>
                <h2>Protocolo Anulado</h2>
                <p>O seu protocolo foi <strong>anulado</strong>.</p>
                <p>Esta decisão foi realizada a seu pedido.</p>
                <br><p>Saudações académicas,</p><p><strong>Equipa de Atendimento</strong><br>ISCAP</p>
            </body></html>
        ";

        return $this->send($userEmail, 'O Seu Protocolo foi anulado', $html, true, [$pdfPath], $this->config['from_email']);
    }

    public function sendPlanEmail(string $userEmail, int $finalDocumentId): bool
    {
        $document = FinalDocument::find($finalDocumentId);
        if (!$document) return false;

        $pdfPath = public_path('uploads/generated_docs/' . $document->pdf_path);
        if (!file_exists($pdfPath)) return false;

        $fullUrl = url('/form') . '?filled_plan_id=' . $finalDocumentId;

        $html = "
            <html><head><meta charset='UTF-8'><title>Plano Pendente</title></head>
            <body>
                <h2>Plano Pendente</h2>
                <p>O plano está <strong>pendente</strong>.</p>
                <p>Por favor, solicite as assinaturas necessárias para a finalização do mesmo.</p>
                <p>O documento encontra-se em anexo a este e-mail.</p>
                <p>Submeta o seu documento: <a href='" . htmlspecialchars($fullUrl) . "'>Clique aqui</a></p>
                <br><p>Saudações académicas,</p><p><strong>Equipa de Atendimento</strong><br>ISCAP</p>
            </body></html>
        ";

        return $this->send($userEmail, 'O Seu Plano está Pendente', $html, true, [$pdfPath], $this->config['from_email']);
    }
}
