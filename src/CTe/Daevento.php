<?php

namespace NFePHP\DA\CTe;

/**
 * Classe para geração do envento do CTe em PDF
 * NOTA: Este documento não está NORMALIZADO, nem é requerido pela SEFAZ
 *
 * @category  Library
 * @package   nfephp-org/sped-da
 * @name      Daevento.php
 * @copyright 2009-2016 NFePHP
 * @license   http://www.gnu.org/licenses/lgpl.html GNU/LGPL v.3
 * @link      http://github.com/nfephp-org/sped-da for the canonical source repository
 * @author    Roberto L. Machado <linux.rlm at gmail dot com>
 */

use Exception;
use NFePHP\DA\Common\Dom;
use NFePHP\DA\Legacy\Pdf;
use NFePHP\DA\Legacy\Common;

class Daevento extends Common
{
    protected $logoAlign = 'C';
    protected $yDados = 0;
    protected $debugMode = 0;
    protected $aEnd = array();
    protected $pdf;
    protected $xml;
    protected $logomarca = '';
    protected $errMsg = '';
    protected $errStatus = false;
    protected $orientacao = 'P';
    protected $papel = 'A4';
    protected $destino = 'I';
    protected $pdfDir = '';
    protected $fontePadrao = 'Times';
    protected $version = '0.1.1';
    protected $wPrint;
    protected $hPrint;
    protected $wCanhoto;
    protected $formatoChave = "#### #### #### #### #### #### #### #### #### #### ####";
    protected $id;
    protected $chCTe;
    protected $tpAmb;
    protected $cOrgao;
    protected $xCorrecao;
    protected $xCondUso;
    protected $dhEvento;
    protected $cStat;
    protected $xMotivo;
    protected $xJust;
    protected $CNPJDest = '';
    protected $CPFDest = '';
    protected $dhRegEvento;
    protected $nProt;
    protected $tpEvento;
    private $dom;
    private $procEventoCTe;
    private $evento;
    private $infEvento;
    private $retEvento;
    private $rinfEvento;
    
    /**
     *__construct
     * @param string $docXML Arquivo XML da cce
     * @param string $sOrientacao (Opcional) Orientação da impressão P-retrato L-Paisagem
     * @param string $sPapel Tamanho do papel (Ex. A4)
     * @param string $sPathLogo Caminho para o arquivo do logo
     * @param string $sDestino Estabelece a direção do envio do documento PDF I-browser D-browser com download S-
     * @param string $sDirPDF Caminho para o diretorio de armazenamento dos arquivos PDF
     * @param string $fonteDACTE Nome da fonte alternativa do DACTE
     * @param array $aEnd array com o endereço do emitente
     * @param integer $mododebug 1-SIM e 0-Não (0 default)
     */
    public function __construct(
        $docXML = '',
        $sOrientacao = '',
        $sPapel = '',
        $sPathLogo = '',
        $sDestino = 'I',
        $sDirPDF = '',
        $fontePDF = '',
        $aEnd = '',
        $mododebug = 0
    ) {
        if (is_numeric($mododebug)) {
            $this->debugMode = $mododebug;
        }
        if ($this->debugMode) {
            //ativar modo debug
            error_reporting(E_ALL);
            ini_set('display_errors', 'On');
        } else {
            //desativar modo debug
            error_reporting(0);
            ini_set('display_errors', 'Off');
        }
        if (is_array($aEnd)) {
            $this->aEnd = $aEnd;
        }
        $this->orientacao   = $sOrientacao;
        $this->papel        = $sPapel;
        $this->pdf          = '';
        $this->logomarca    = $sPathLogo;
        $this->destino      = $sDestino;
        $this->pdfDir       = $sDirPDF;
        // verifica se foi passa a fonte a ser usada
        if (empty($fontePDF)) {
            $this->fontePadrao = 'Times';
        } else {
            $this->fontePadrao = $fontePDF;
        }
        //se for passado o xml
        if (!is_file($docXML)) {
            if (empty($docXML)) {
                $this->errMsg = 'Um caminho ou um arquivo xml de evento de CTe deve ser passado!';
                $this->errStatus = true;
                return false;
            }
        } else {
            $docXML = file_get_contents($docXML);
        }
        $this->dom = new DomDocument;
        $this->dom->loadXML($docXML);
        $this->procEventoCTe    = $this->dom->getElementsByTagName("procEventoCTe")->item(0);
        $this->evento           = $this->dom->getElementsByTagName("eventoCTe")->item(0);
        $this->infEvento        = $this->evento->getElementsByTagName("infEvento")->item(0);
        $this->retEvento        = $this->dom->getElementsByTagName("retEventoCTe")->item(0);
        $this->rinfEvento       = $this->retEvento->getElementsByTagName("infEvento")->item(0);
        $this->tpEvento         = $this->infEvento->getElementsByTagName("tpEvento")->item(0)->nodeValue;
        if (!in_array($this->tpEvento, array('110110', '110111'))) {
            $this->errMsg = 'Evento não implementado '.$tpEvento.' !!';
            $this->errStatus = true;
            return false;
        }
        $this->id = str_replace('ID', '', $this->infEvento->getAttribute("Id"));
        $this->chCTe = $this->infEvento->getElementsByTagName("chCTe")->item(0)->nodeValue;
        $this->aEnd['CNPJ']=substr($this->chCTe, 6, 14);
        $this->tpAmb = $this->infEvento->getElementsByTagName("tpAmb")->item(0)->nodeValue;
        $this->cOrgao = $this->infEvento->getElementsByTagName("cOrgao")->item(0)->nodeValue;
        $this->xCorrecao = $this->infEvento->getElementsByTagName("xCorrecao")->item(0);
        $this->xCorrecao=(empty($this->xCorrecao)?'':$this->xCorrecao->nodeValue);
        $this->xCondUso = $this->infEvento->getElementsByTagName("xCondUso")->item(0);
        $this->xCondUso=(empty($this->xCondUso)?'':$this->xCondUso->nodeValue);
        $this->xJust =  $this->infEvento->getElementsByTagName("xJust")->item(0);
        $this->xJust=(empty($this->xJust)?'':$this->xJust->nodeValue);
        $this->dhEvento = $this->infEvento->getElementsByTagName("dhEvento")->item(0)->nodeValue;
        $this->cStat = $this->rinfEvento->getElementsByTagName("cStat")->item(0)->nodeValue;
        $this->xMotivo = $this->rinfEvento->getElementsByTagName("xMotivo")->item(0)->nodeValue;
        $this->CNPJDest = !empty($this->rinfEvento->getElementsByTagName("CNPJDest")->item(0)->nodeValue)?
                $this->rinfEvento->getElementsByTagName("CNPJDest")->item(0)->nodeValue:'';
        $this->CPFDest =  !empty($this->rinfEvento->getElementsByTagName("CPFDest")->item(0)->nodeValue)?
                $this->rinfEvento->getElementsByTagName("CPFDest")->item(0)->nodeValue:'';
        $this->dhRegEvento = $this->rinfEvento->getElementsByTagName("dhRegEvento")->item(0)->nodeValue;
        $this->nProt = $this->rinfEvento->getElementsByTagName("nProt")->item(0)->nodeValue;
    }
  
    /**
     * monta
     * @param string $orientacao
     * @param string $papel
     * @param string $logoAlign
     * @param string $situacao_externa
     * @param string $classPDF
     * @return string
     */
    public function monta(
        $orientacao = '',
        $papel = 'A4',
        $logoAlign = 'C',
        $classPDF = false
    ) {
        return $this->montaDAEventoCTe(
            $orientacao,
            $papel,
            $logoAlign,
            $classPDF
        );
    }
    
    /**
     * printDocument
     * @param string $nome
     * @param string $destino
     * @param string $printer
     * @return string pdf
     */
    public function printDocument($nome = '', $destino = 'I', $printer = '')
    {
        return $this->printDAEventoCTe($nome, $destino, $printer);
    }
    
    /**
     * montaDAEventoCTe
     * Esta função monta a DAEventoCTe conforme as informações fornecidas para a classe
     * durante sua construção.
     * A definição de margens e posições iniciais para a impressão são estabelecidas no
     * pelo conteúdo da funçao e podem ser modificados.
     *
     * @param string $orientacao (Opcional) Estabelece a orientação da impressão (ex. P-retrato),
     *               se nada for fornecido será usado o padrão da CTe
     * @param string $papel (Opcional) Estabelece o tamanho do papel (ex. A4)
     * @return string O ID do evento extraido do arquivo XML
     */
    public function montaDAEventoCTe(
        $orientacao = '',
        $papel = 'A4',
        $logoAlign = 'C',
        $classPDF = false
    ) {
        if ($orientacao == '') {
            $orientacao = 'P';
        }
        $this->orientacao = $orientacao;
        $this->papel = $papel;
        $this->logoAlign = $logoAlign;
        if ($classPDF!==false) {
            $this->pdf = $classPDF ;
        } else {
            $this->pdf = new Pdf($this->orientacao, 'mm', $this->papel);
        }
        if ($this->orientacao == 'P') {
            // margens do PDF
            $margSup = 2;
            $margEsq = 2;
            $margDir = 2;
            // posição inicial do relatorio
            $xInic = 1;
            $yInic = 1;
            if ($this->papel =='A4') { //A4 210x297mm
                $maxW = 210;
                $maxH = 297;
            }
        } else {
            // margens do PDF
            $margSup = 3;
            $margEsq = 3;
            $margDir = 3;
            // posição inicial do relatorio
            $xInic = 5;
            $yInic = 5;
            if ($papel =='A4') {
                //A4 210x297mm
                $maxH = 210;
                $maxW = 297;
            }
        }
        //largura imprimivel em mm
        $this->wPrint = $maxW-($margEsq+$xInic);
        //comprimento imprimivel em mm
        $this->hPrint = $maxH-($margSup+$yInic);
        // estabelece contagem de paginas
        $this->pdf->aliasNbPages();
        // fixa as margens
        $this->pdf->setMargins($margEsq, $margSup, $margDir);
        $this->pdf->setDrawColor(0, 0, 0);
        $this->pdf->setFillColor(255, 255, 255);
        // inicia o documento
        $this->pdf->open();
        // adiciona a primeira página
        $this->pdf->addPage($this->orientacao, $this->papel);
        $this->pdf->setLineWidth(0.1);
        $this->pdf->setTextColor(0, 0, 0);
        //montagem da página
        $pag = 1;
        $x = $xInic;
        $y = $yInic;
        //coloca o cabeçalho
        $y = $this->zCabecalho($x, $y, $pag, $situacao_externa);
        //coloca os dados da CCe
        $y = $this->zCorpo($x, $y+15);
        //coloca os dados da CCe
        $y = $this->zRodape($x, $y+$this->hPrint-20);
        //retorna o ID do evento
        if ($classPDF !==false) {
            $aR = array(
                'id'=>$this->id,
                'classe_PDF'=>$this->pdf);
            return $aR;
        } else {
            return $this->id;
        }
    }
    
    /**
     * zCabecalho
     * @param integer $x
     * @param integer $y
     * @param integer $pag
     * @return integer
     */
    private function zCabecalho(
        $x,
        $y,
        $pag
    ) {
        $oldX = $x;
        $oldY = $y;
        $maxW = $this->wPrint;
        //####################################################################################
        //coluna esquerda identificação do emitente
        $w = round($maxW*0.41, 0);// 80;
        if ($this->orientacao == 'P') {
            $aFont = array('font'=>$this->fontePadrao, 'size'=>6, 'style'=>'I');
        } else {
            $aFont = array('font'=>$this->fontePadrao, 'size'=>8, 'style'=>'B');
        }
        $w1 = $w;
        $h=32;
        $oldY += $h;
        $this->pTextBox($x, $y, $w, $h);
        $texto = 'IDENTIFICAÇÃO DO EMITENTE';
        $this->pTextBox($x, $y, $w, 5, $texto, $aFont, 'T', 'C', 0, '');
        if (is_file($this->logomarca)) {
            $logoInfo = getimagesize($this->logomarca);
            //largura da imagem em mm
            $logoWmm = ($logoInfo[0]/72)*25.4;
            //altura da imagem em mm
            $logoHmm = ($logoInfo[1]/72)*25.4;
            if ($this->logoAlign=='L') {
                $nImgW = round($w/3, 0);
                $nImgH = round($logoHmm * ($nImgW/$logoWmm), 0);
                $xImg = $x+1;
                $yImg = round(($h-$nImgH)/2, 0)+$y;
                //estabelecer posições do texto
                $x1 = round($xImg + $nImgW +1, 0);
                $y1 = round($h/3+$y, 0);
                $tw = round(2*$w/3, 0);
            }
            if ($this->logoAlign=='C') {
                $nImgH = round($h/3, 0);
                $nImgW = round($logoWmm * ($nImgH/$logoHmm), 0);
                $xImg = round(($w-$nImgW)/2+$x, 0);
                $yImg = $y+3;
                $x1 = $x;
                $y1 = round($yImg + $nImgH + 1, 0);
                $tw = $w;
            }
            if ($this->logoAlign=='R') {
                $nImgW = round($w/3, 0);
                $nImgH = round($logoHmm * ($nImgW/$logoWmm), 0);
                $xImg = round($x+($w-(1+$nImgW)), 0);
                $yImg = round(($h-$nImgH)/2, 0)+$y;
                $x1 = $x;
                $y1 = round($h/3+$y, 0);
                $tw = round(2*$w/3, 0);
            }
            $this->pdf->Image($this->logomarca, $xImg, $yImg, $nImgW, $nImgH, 'jpeg');
        } else {
            $x1 = $x;
            $y1 = round($h/3+$y, 0);
            $tw = $w;
        }
        //Nome emitente
        $aFont = array('font'=>$this->fontePadrao, 'size'=>12, 'style'=>'B');
        $texto = (isset($this->aEnd['razao'])?$this->aEnd['razao']:'');
        $this->pTextBox($x1, $y1, $tw, 8, $texto, $aFont, 'T', 'C', 0, '');
        //endereço
        $y1 = $y1+6;
        $aFont = array('font'=>$this->fontePadrao, 'size'=>8, 'style'=>'');
        $lgr = (isset($this->aEnd['logradouro'])?$this->aEnd['logradouro']:'');
        $nro = (isset($this->aEnd['numero'])?$this->aEnd['numero']:'');
        $cpl = (isset($this->aEnd['complemento'])?$this->aEnd['complemento']:'');
        $bairro = (isset($this->aEnd['bairro'])?$this->aEnd['bairro']:'');
        $CEP = (isset($this->aEnd['CEP'])?$this->aEnd['CEP']:'');
        $CEP = $this->pFormat($CEP, "#####-###");
        $mun = (isset($this->aEnd['municipio'])?$this->aEnd['municipio']:'');
        $UF = (isset($this->aEnd['UF'])?$this->aEnd['UF']:'');
        $fone = (isset($this->aEnd['telefone'])?$this->aEnd['telefone']:'');
        $email = (isset($this->aEnd['email'])?$this->aEnd['email']:'');
        $foneLen = strlen($fone);
        if ($foneLen > 0) {
            $fone2 = substr($fone, 0, $foneLen-4);
            $fone1 = substr($fone, 0, $foneLen-8);
            $fone = '(' . $fone1 . ') ' . substr($fone2, -4) . '-' . substr($fone, -4);
        } else {
            $fone = '';
        }
        if ($email != '') {
            $email = 'Email: '.$email;
        }
        $texto = "";
        $tmp_txt = trim(($lgr!=''?"$lgr, ":'').($nro!=0?$nro:"SN").($cpl!=''?" - $cpl":''));
        $tmp_txt = ($tmp_txt=='SN'?'':$tmp_txt);
        $texto .= ($texto!='' && $tmp_txt!=''?"\n":'').$tmp_txt;
        $tmp_txt = trim($bairro.($bairro!='' && $CEP!=''?" - ":'').$CEP);
        $texto .= ($texto!='' && $tmp_txt!=''?"\n":'').$tmp_txt;
        $tmp_txt = $mun;
        $tmp_txt.= ($tmp_txt!='' && $UF!=''?" - ":'').$UF;
        $tmp_txt.= ($tmp_txt!='' && $fone!=''?" - ":'').$fone;
        $texto .= ($texto!='' && $tmp_txt!=''?"\n":'').$tmp_txt;
        $tmp_txt = $email;
        $texto .= ($texto!='' && $tmp_txt!=''?"\n":'').$tmp_txt;
        $this->pTextBox($x1, $y1-2, $tw, 8, $texto, $aFont, 'T', 'C', 0, '');
        //##################################################
        $w2 = round($maxW - $w, 0);
        $x += $w;
        $this->pTextBox($x, $y, $w2, $h);
        $y1 = $y + $h;
        $aFont = array('font'=>$this->fontePadrao, 'size'=>16, 'style'=>'B');
        if ($this->tpEvento=='110110') {
            $texto='Representação Gráfica de CCe';
        } else {
            $texto='Representação Gráfica de Evento';
        }
        $this->pTextBox($x, $y+2, $w2, 8, $texto, $aFont, 'T', 'C', 0, '');
        $aFont = array('font'=>$this->fontePadrao, 'size'=>12, 'style'=>'I');
        if ($this->tpEvento=='110110') {
            $texto='(Carta de Correção Eletrônica)';
        } elseif ($this->tpEvento=='110111') {
            $texto='(Cancelamento de CTe)';
        }
        $this->pTextBox($x, $y+7, $w2, 8, $texto, $aFont, 'T', 'C', 0, '');
        $texto = 'ID do Evento: '.$this->id;
        $aFont = array('font'=>$this->fontePadrao, 'size'=>10, 'style'=>'');
        $this->pTextBox($x, $y+15, $w2, 8, $texto, $aFont, 'T', 'L', 0, '');
        $tsHora = $this->pConvertTime($this->dhEvento);
        $texto = 'Criado em : '. date('d/m/Y   H:i:s', $tsHora);
        $this->pTextBox($x, $y+20, $w2, 8, $texto, $aFont, 'T', 'L', 0, '');
        $tsHora = $this->pConvertTime($this->dhRegEvento);
        $texto = 'Prococolo: '.$this->nProt.'  -  Registrado na SEFAZ em: '.date('d/m/Y   H:i:s', $tsHora);
        $this->pTextBox($x, $y+25, $w2, 8, $texto, $aFont, 'T', 'L', 0, '');
        //####################################################
        $x = $oldX;
        $this->pTextBox($x, $y1, $maxW, 40);
        $sY = $y1+40;
        if ($this->tpEvento=='110110') {
            $texto = 'De acordo com as determinações legais vigentes, vimos por meio '
                    . 'desta comunicar-lhe que o Conhecimento de Transporte, abaixo referenciado, '
                    . 'contêm irregularidades que estão destacadas e suas respectivas '
                    . 'correções, solicitamos que sejam aplicadas essas correções ao '
                    . 'executar seus lançamentos fiscais.';
        } elseif ($this->tpEvento=='110111') {
            $texto = 'De acordo com as determinações legais vigentes, vimos por meio '
                    . 'desta comunicar-lhe que o  Conhecimento de Transporte, abaixo referenciado, está '
                    . 'cancelado, solicitamos que sejam aplicadas essas correções ao '
                    . 'executar seus lançamentos fiscais.';
        }
        $aFont = array('font'=>$this->fontePadrao, 'size'=>10, 'style'=>'');
        $this->pTextBox($x+5, $y1, $maxW-5, 20, $texto, $aFont, 'T', 'L', 0, '', false);
        //############################################
        $x = $oldX;
        $y = $y1;
        $aFont = array('font'=>$this->fontePadrao, 'size'=>12, 'style'=>'B');
        $numNF = substr($this->chCTe, 25, 9);
        $serie = substr($this->chCTe, 22, 3);
        $numNF = $this->pFormat($numNF, "###.###.###");
        $texto = "Conhecimento: " . $numNF .'  -   Série: '.$serie;
        $this->pTextBox($x+2, $y+19, $w2, 8, $texto, $aFont, 'T', 'L', 0, '');
        $bW = 87;
        $bH = 15;
        $x = 55;
        $y = $y1+13;
        $w = $maxW;
        $this->pdf->setFillColor(0, 0, 0);
        $this->pdf->code128($x+(($w-$bW)/2), $y+2, $this->chCTe, $bW, $bH);
        $this->pdf->setFillColor(255, 255, 255);
        $y1 = $y+2+$bH;
        $aFont = array('font'=>$this->fontePadrao, 'size'=>10, 'style'=>'');
        $texto = $this->pFormat($this->chCTe, $this->formatoChave);
        $this->pTextBox($x, $y1, $w-2, $h, $texto, $aFont, 'T', 'C', 0, '');
        $retVal = $sY+2;
        if ($this->tpEvento=='110110') {
            $x = $oldX;
            $this->pTextBox($x, $sY, $maxW, 15);
            $texto = $this->xCondUso;
            $aFont = array('font'=>$this->fontePadrao, 'size'=>8, 'style'=>'I');
            $this->pTextBox($x+2, $sY+2, $maxW-2, 15, $texto, $aFont, 'T', 'L', 0, '', false);
            $retVal = $sY+2;
        }
        if ($this->tpAmb != 1) {
            $x = 10;
            if ($this->orientacao == 'P') {
                $y = round($this->hPrint*2/3, 0);
            } else {
                $y = round($this->hPrint/2, 0);
            }
            $h = 5;
            $w = $maxW-(2*$x);
            $this->pdf->setTextColor(90, 90, 90);
            $texto = "SEM VALOR FISCAL";
            $aFont = array('font'=>$this->fontePadrao, 'size'=>48, 'style'=>'B');
            $this->pTextBox($x, $y, $w, $h, $texto, $aFont, 'C', 'C', 0, '');
            $aFont = array('font'=>$this->fontePadrao, 'size'=>30, 'style'=>'B');
            $texto = "AMBIENTE DE HOMOLOGAÇÃO";
            $this->pTextBox($x, $y+14, $w, $h, $texto, $aFont, 'C', 'C', 0, '');
            $this->pdf->setTextColor(0, 0, 0);
        }
        return $retVal;
    }
    
    /**
     * zCorpo
     * @param integer $x
     * @param integer $y
     */
    private function zCorpo($x, $y)
    {
        $maxW = $this->wPrint;
        if ($this->tpEvento=='110110') {
            $texto = 'CORREÇÕES A SEREM CONSIDERADAS';
        } else {
            $texto = 'JUSTIFICATIVA DO CANCELAMENTO';
        }
        $aFont = array('font'=>$this->fontePadrao, 'size'=>10, 'style'=>'B');
        $this->pTextBox($x, $y, $maxW, 5, $texto, $aFont, 'T', 'L', 0, '', false);
        $y += 5;
        $this->pTextBox($x, $y, $maxW, 190);
        if ($this->tpEvento=='110110') {
            $texto = $this->xCorrecao;
        } elseif ($this->tpEvento=='110111') {
            $texto = $this->xJust;
        }
        $aFont = array('font'=>$this->fontePadrao, 'size'=>12, 'style'=>'B');
        $this->pTextBox($x+2, $y+2, $maxW-2, 150, $texto, $aFont, 'T', 'L', 0, '', false);
    }
    
    /**
     * zRodape
     * @param integer $x
     * @param integer $y
     */
    private function zRodape($x, $y)
    {
        $w = $this->wPrint;
        if ($this->tpEvento=='110110') {
            $texto = "Este documento é uma representação gráfica da CCe e foi "
                    . "impresso apenas para sua informação e não possue validade fiscal."
                    . "\n A CCe deve ser recebida e mantida em arquivo eletrônico XML e "
                    . "pode ser consultada através dos Portais das SEFAZ.";
        } elseif ($this->tpEvento=='110111') {
            $texto = "Este documento é uma representação gráfica do evento de CTe e foi "
                    . "impresso apenas para sua informação e não possue validade fiscal."
                    . "\n O Evento deve ser recebido e mantido em arquivo eletrônico XML e "
                    . "pode ser consultada através dos Portais das SEFAZ.";
        }
        $aFont = array('font'=>$this->fontePadrao, 'size'=>10, 'style'=>'I');
        $this->pTextBox($x, $y, $w, 20, $texto, $aFont, 'T', 'C', 0, '', false);
        $y = $this->hPrint -4;
        $texto = "Impresso em  ". date('d/m/Y   H:i:s');
        $w = $this->wPrint-4;
        $aFont = array('font'=>$this->fontePadrao, 'size'=>6, 'style'=>'I');
        $this->pTextBox($x, $y, $w, 4, $texto, $aFont, 'T', 'L', 0, '');
        $texto = "Daevento ver. " . $this->version
            .  "  Powered by NFePHP (GNU/GPLv3 GNU/LGPLv3) © www.nfephp.org";
        $aFont = array('font'=>$this->fontePadrao, 'size'=>6, 'style'=>'I');
        $this->pTextBox($x, $y, $w, 4, $texto, $aFont, 'T', 'R', 0, 'http://www.nfephp.org');
    }
    
    /**
     * printDAEventoCTe
     * @param string $nome
     * @param string $destino
     * @param string $printer
     * @return type
     */
    public function printDAEventoCTe($nome = '', $destino = 'I', $printer = '')
    {
        $arq = $this->pdf->Output($nome, $destino);
        if ($destino == 'S') {
            //aqui pode entrar a rotina de impressão direta
        }
        return $arq;
    }
}
