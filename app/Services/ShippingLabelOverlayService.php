<?php

namespace App\Services;

use setasign\Fpdi\Fpdi;
use App\Models\StorefrontSetting;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ShippingLabelOverlayService
{
    /**
     * Shrinks the original PDF and adds a branding footer.
     * 
     * @param string $pdfContent The raw PDF content from the marketplace
     * @param array|null $config Optional custom settings
     * @return string Modified PDF stream
     */
    public function overlayPdfContent(string $pdfContent, ?array $config = null): string
    {
        $isBrandingEnabled = $this->getSetting('marketplace_print_branding', '1', $config);
        if ($isBrandingEnabled !== '1') {
            return $pdfContent;
        }

        // FPDF requires a physical file to import
        $tmpFile = tempnam(sys_get_temp_dir(), 'resi_in_');
        file_put_contents($tmpFile, $pdfContent);
        
        // Shopee PDFs use compression/cross-reference streams not supported by free FPDI.
        // Use qpdf to uncompress the file if qpdf is installed.
        $qpdfPath = exec('which qpdf');
        if (empty($qpdfPath)) {
            $possiblePaths = ['/usr/local/bin/qpdf', '/opt/homebrew/bin/qpdf', '/usr/bin/qpdf'];
            foreach ($possiblePaths as $path) {
                if (file_exists($path)) {
                    $qpdfPath = $path;
                    break;
                }
            }
        }
        
        if ($qpdfPath) {
            $uncompressedFile = tempnam(sys_get_temp_dir(), 'resi_uncomp_');
            exec(sprintf("%s --stream-data=uncompress --empty --pages %s 1-z -- %s 2>/dev/null", escapeshellarg($qpdfPath), escapeshellarg($tmpFile), escapeshellarg($uncompressedFile)), $output, $returnVar);
            if ($returnVar === 0 && file_exists($uncompressedFile)) {
                unlink($tmpFile);
                $tmpFile = $uncompressedFile;
            }
        }
        
        $pdf = new Fpdi();
        $pdf->SetAutoPageBreak(false);
        
        try {
            $pageCount = $pdf->setSourceFile($tmpFile);
            
            // Parse social accounts
            $accountsJson = $this->getSetting('marketplace_social_accounts', '[]', $config);
            $accounts = json_decode($accountsJson, true) ?: [];
            
            // For fallback to legacy settings if accounts array is empty
            if (empty($accounts)) {
                $legacyPlat = $this->getSetting('marketplace_social_platform', 'Instagram', $config);
                $legacyUser = $this->getSetting('marketplace_social_username', '', $config);
                if ($legacyUser) {
                    $accounts[] = ['platform' => $legacyPlat, 'username' => $legacyUser];
                }
            }
            
            // Format accounts for display
            $socialAccountsFormatted = [];
            foreach ($accounts as $acc) {
                $plat = $acc['platform'];
                $user = $acc['username'];
                
                $handle = $user;
                if (str_contains($handle, '/')) {
                    $handle = trim(parse_url($handle, PHP_URL_PATH), '/');
                }
                if ($handle && !str_starts_with($handle, '@') && !in_array($plat, ['Website', 'Web', 'WhatsApp', 'WA'])) {
                    $handle = '@' . $handle;
                }
                
                $icon = null;
                if (in_array($plat, ['IG', 'Instagram'])) $icon = public_path('img/social/IG.png');
                else if (in_array($plat, ['TT', 'TikTok'])) $icon = public_path('img/social/TT.png');
                else if (in_array($plat, ['FB', 'Facebook'])) $icon = public_path('img/social/FB.png');
                else if (in_array($plat, ['WA', 'WhatsApp'])) $icon = public_path('img/social/WA.png');
                else if (in_array($plat, ['Web', 'Website'])) $icon = public_path('img/social/Web.png');
                
                $socialAccountsFormatted[] = [
                    'icon' => $icon,
                    'text' => $handle
                ];
            }
            
            $footerImagePath = $this->getSetting('marketplace_footer_image', '', $config);
            $footerTemplate = $this->getSetting('marketplace_footer_template', 'none', $config);
            
            $footerImageFull = null;
            if ($footerImagePath) {
                $footerImageFull = storage_path('app/public/' . $footerImagePath);
            } else if ($footerTemplate && $footerTemplate !== 'none') {
                // Support legacy numeric values like "4"
                if (preg_match('/^\d+$/', $footerTemplate)) {
                    $footerTemplate = 'template_' . $footerTemplate . '.png';
                }
                $footerImageFull = storage_path('app/public/templates/footers/' . $footerTemplate);
            }
            
            $isFooterPdf = false;
            $footerPdfTpl = null;
            $footerImgSize = false;
            
            if ($footerImageFull && file_exists($footerImageFull)) {
                $ext = strtolower(pathinfo($footerImageFull, PATHINFO_EXTENSION));
                if ($ext === 'pdf') {
                    $isFooterPdf = true;
                    try {
                        $pdf->setSourceFile($footerImageFull);
                        $footerPdfTpl = $pdf->importPage(1);
                        $rawSize = $pdf->getTemplateSize($footerPdfTpl);
                        $footerImgSize = [$rawSize['width'], $rawSize['height']];
                        // Reset source back to main PDF
                        $pdf->setSourceFile($tmpFile);
                    } catch (\Exception $e) {
                        Log::error("Failed to load PDF footer: " . $e->getMessage());
                        $footerImgSize = false;
                        $pdf->setSourceFile($tmpFile);
                    }
                } else {
                    $footerImgSize = @getimagesize($footerImageFull);
                }
            }

            for ($i = 1; $i <= $pageCount; $i++) {
                $tplId = $pdf->importPage($i);
                
                // ... setup scaling
                $size = $pdf->getTemplateSize($tplId);
                $width = $size['width'];
                $height = $size['height'];
                
                $pdf->AddPage($size['orientation'], [$width, $height]);
                
                $senderName = $this->getSetting('marketplace_sender_name', '', $config);
                $senderPhone = $this->getSetting('marketplace_sender_phone', '', $config);
                $greetingStr = $this->getSetting('marketplace_footer_greeting', 'Terima kasih telah berbelanja!', $config);
                $alignStr = $this->getSetting('marketplace_footer_alignment', 'C', $config);
                $showDivider = $this->getSetting('marketplace_footer_divider', '0', $config) == '1';
                
                // Cap footer height at 20% of document height (approx 30mm for thermal)
                $maxFooterHeight = $height * 0.20;
                $reservedBottom = 0;
                
                $footerImgWidth = 0;
                $footerImgHeight = 0;
                
                if ($footerImgSize) {
                    $scaledWidth = $width; // Full width, no side margins
                    $scaledHeight = ($footerImgSize[1] / $footerImgSize[0]) * $scaledWidth;
                    
                    if ($scaledHeight > $maxFooterHeight) {
                        $scaledHeight = $maxFooterHeight;
                        $scaledWidth = ($footerImgSize[0] / $footerImgSize[1]) * $scaledHeight;
                    }
                    
                    $footerImgWidth = $scaledWidth;
                    $footerImgHeight = $scaledHeight;
                    // Reserve space for image + social media row below it
                    $socialRowHeight = !empty($socialAccountsFormatted) ? 3.5 : 0;
                    $reservedBottom = $footerImgHeight + $socialRowHeight + 1;
                } else {
                    if ($senderName || $senderPhone) {
                        $reservedBottom = 12; // Moderate space for text footer
                    }
                }
                
                // Draw original PDF at 100% width, but slightly squished height!
                // This keeps the label full width (tidak mengecil di sisi kiri/kanan)
                // sambil memberi ruang lega untuk footer di bawah tanpa menutupi teks asli.
                $pdf->useTemplate($tplId, 0, 0, $width, $height - $reservedBottom);
                
                // Draw white rectangle to cover the bottom area just in case
                $pdf->SetFillColor(255, 255, 255);
                $pdf->Rect(0, $height - $reservedBottom, $width, $reservedBottom, 'F');
                
                // Draw Footer
                $pdf->SetY($height - $reservedBottom + 1);
                
                if ($showDivider) {
                    $pdf->SetDrawColor(200, 200, 200);
                    // Draw a simple dashed line manually by short segments
                    $yLine = $height - $reservedBottom + 1;
                    for ($xLine = 0; $xLine < $width; $xLine += 4) {
                        $pdf->Line($xLine, $yLine, $xLine + 2, $yLine);
                    }
                    $pdf->SetY($height - $reservedBottom + 2);
                }
                
                if ($footerImgSize && $footerImgHeight > 0) {
                    // Full width, flush to edges
                    $xPos = ($width - $footerImgWidth) / 2;
                    
                    // Calculate social media height needed
                    $socialHeight = 0;
                    if (!empty($socialAccountsFormatted)) {
                        $socialHeight = 3.5; // ~3.5mm for social row to keep it compact
                    }
                    
                    // Place image above social row
                    $imgY = $height - $footerImgHeight - $socialHeight;
                    
                    if ($isFooterPdf && $footerPdfTpl) {
                        $pdf->useTemplate($footerPdfTpl, $xPos, $imgY, $footerImgWidth, $footerImgHeight);
                    } else {
                        $pdf->Image($footerImageFull, $xPos, $imgY, $footerImgWidth, $footerImgHeight);
                    }
                    
                    // Set Y after image for social media
                    $pdf->SetY($imgY + $footerImgHeight + 0.5);
                } else {
                    if ($senderName || $senderPhone) {
                        $pdf->SetFont('Arial', 'B', 10);
                        $pdf->SetTextColor(0, 0, 0);
                        $senderText = "PENGIRIM: " . $senderName;
                        if ($senderName && $senderPhone) $senderText .= " - ";
                        if ($senderPhone) $senderText .= $senderPhone;
                        if (!$senderName) $senderText = "PENGIRIM: " . $senderPhone;
                        
                        $senderText = iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $senderText);
                        $pdf->Cell(0, 4, $senderText, 0, 1, $alignStr);
                    }
                    
                    if ($greetingStr) {
                        $pdf->SetFont('Arial', 'B', 7);
                        $pdf->SetTextColor(0, 0, 0);
                        $greeting = iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $greetingStr);
                        $pdf->Cell(0, 3, $greeting, 0, 1, $alignStr);
                    }
                }
                
                // Always render social media accounts below footer (image or text)
                if (!empty($socialAccountsFormatted)) {
                    $pdf->SetFont('Arial', '', 7);
                    $pdf->SetTextColor(60, 60, 60);
                    
                    $totalWidth = 0;
                    $spacing = 1.5;
                    $itemSpacing = 4;
                    
                    foreach ($socialAccountsFormatted as $acc) {
                        if ($acc['icon'] && file_exists($acc['icon'])) {
                            $totalWidth += 2.5 + $spacing;
                        }
                        $totalWidth += $pdf->GetStringWidth($acc['text']);
                    }
                    $totalWidth += (count($socialAccountsFormatted) - 1) * $itemSpacing;
                    
                    $startX = $alignStr === 'C' ? ($width - $totalWidth) / 2 : 3;
                    $currentX = max(2, $startX);
                    $currentY = $pdf->GetY();
                    
                    foreach ($socialAccountsFormatted as $acc) {
                        if ($acc['icon'] && file_exists($acc['icon'])) {
                            $pdf->Image($acc['icon'], $currentX, $currentY + 0.3, 2.5, 2.5);
                            $currentX += 2.5 + $spacing;
                        }
                        
                        $pdf->SetXY($currentX, $currentY);
                        $txt = iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $acc['text']);
                        $pdf->Cell($pdf->GetStringWidth($acc['text']), 3.5, $txt, 0, 0, 'L');
                        
                        $currentX += $pdf->GetStringWidth($acc['text']) + $itemSpacing;
                    }
                    $pdf->Ln(4);
                }
                
                if ($this->getSetting('marketplace_footer_border', '0', $config) == '1') {
                    $pdf->SetDrawColor(0, 0, 0);
                    $pdf->SetLineWidth(0.5);
                    $pdf->Rect(2, 2, $width - 4, $height - 4);
                }
                
                // === EXTRA PAGE: GREETING CARD ===
                $printGreetingCard = $this->getSetting('marketplace_print_greeting_card', '0', $config) == '1';
                if ($printGreetingCard) {
                    $pdf->AddPage($size['orientation'], [$width, $height]);
                    
                    $greetingImageFull = null;
                    $customGreetingImg = $this->getSetting('marketplace_greeting_card_image', '', $config);
                    
                    if (!empty($customGreetingImg) && file_exists(storage_path('app/public/' . $customGreetingImg))) {
                        $greetingImageFull = storage_path('app/public/' . $customGreetingImg);
                    } else {
                        $gTpl = $this->getSetting('marketplace_greeting_card_template', 'template_1.png', $config);
                        if ($gTpl !== 'none') {
                            // Support legacy settings "1", "2", "3"
                            if (in_array($gTpl, ['1', '2', '3'])) {
                                $gTpl = 'template_' . $gTpl . '.png';
                            }
                            $tplPath = storage_path('app/public/templates/greetings/' . $gTpl);
                            if (file_exists($tplPath)) {
                                $greetingImageFull = $tplPath;
                            }
                        }
                    }
                    
                    if ($greetingImageFull) {
                        $ext = strtolower(pathinfo($greetingImageFull, PATHINFO_EXTENSION));
                        if ($ext === 'pdf') {
                            try {
                                $pdf->setSourceFile($greetingImageFull);
                                $gTplId = $pdf->importPage(1);
                                $pdf->useTemplate($gTplId, 0, 0, $width, $height);
                                $pdf->setSourceFile($tmpFile); // reset source
                            } catch (\Exception $e) {
                                \Illuminate\Support\Facades\Log::error("Failed to load PDF greeting card: " . $e->getMessage());
                                $pdf->setSourceFile($tmpFile);
                            }
                        } else {
                            // Add 4mm safe margin to prevent thermal printer cutoff
                            $m = 4;
                            $pdf->Image($greetingImageFull, $m, $m, $width - ($m * 2), $height - ($m * 2));
                        }
                    } else {
                        // Fallback text if no image
                        $pdf->SetFont('Helvetica', 'B', 14);
                        $pdf->SetXY(0, ($height / 2) - 5);
                        $pdf->Cell($width, 10, 'Thank you for your order!', 0, 1, 'C');
                    }
                }
            }
            
            return $pdf->Output('S'); // Return as string
        } catch (\Exception $e) {
            Log::error("Failed to overlay shipping document PDF: " . $e->getMessage());
            // If failed, return original
            return $pdfContent;
        } finally {
            if (file_exists($tmpFile)) {
                unlink($tmpFile);
            }
        }
    }
    
    private function getSetting($key, $default = '', $config = null)
    {
        if (is_array($config) && array_key_exists($key, $config)) {
            return $config[$key];
        }
        return \App\Models\SystemSetting::get($key, $default);
    }
}
