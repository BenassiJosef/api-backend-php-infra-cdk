<?php
/**
 * Created by jamieaitken on 12/04/2018 at 23:15
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Locations\MenuGenerator;


class MenuTranslator
{

    public static function getDashBoardTitle(string $language)
    {
        switch ($language) {
            case 'es':
                return 'Tablero';
            case 'de':
                return 'Instrumententafel';
            case 'br':
                return 'painel de controle';
            case 'jp':
                return 'ダッシュボード';
        }

        return 'Dashboard';
    }

    public static function getReviewTitle(string $language)
    {
        switch ($language) {
            case 'es':
                return '';
            case 'de':
                return '';
            case 'br':
                return '';
            case 'jp':
                return '';
        }

        return 'Reviews';
    }

    public static function getReviewSubmissionsSubTitle(string $language)
    {
        switch ($language) {
            case 'es':
                return '';
            case 'de':
                return '';
            case 'br':
                return '';
            case 'jp':
                return '';
        }

        return 'Submissions';
    }

    public static function getReviewTemplateSubTitle(string $language)
    {
        switch ($language) {
            case 'es':
                return '';
            case 'de':
                return '';
            case 'br':
                return '';
            case 'jp':
                return '';
        }

        return 'Template';
    }

    public static function getReviewSetupSubTitle(string $language)
    {
        switch ($language) {
            case 'es':
                return '';
            case 'de':
                return '';
            case 'br':
                return '';
            case 'jp':
                return '';
        }

        return 'Setup';
    }

    public static function getOverviewSubMenuTitle(string $language)
    {
        switch ($language) {
            case 'es':
                return 'Visión de conjunto';
            case 'de':
                return 'Überblick';
            case 'br':
                return 'Visão geral';
            case 'jp':
                return '概要';
        }

        return 'Overview';
    }

    public static function getCustomersSubMenuTitle(string $language)
    {
        switch ($language) {
            case 'es':
                return 'Clientes';
            case 'de':
                return 'Kunden';
            case 'br':
                return 'Clientes';
            case 'jp':
                return '顧客';
        }

        return 'Customers';
    }

    public static function getImpressionsSubMenuTitle(string $language)
    {
        switch ($language) {
            case 'es':
                return 'Impressions';
            case 'de':
                return 'Impressions';
            case 'br':
                return 'Impressions';
            case 'jp':
                return 'Impressions';
        }

        return 'Impressions';
    }

    public static function getInsightTitle(string $language)
    {
        switch ($language) {
            case 'es':
                return 'Visión';
            case 'de':
                return 'Einblick';
            case 'br':
                return 'Discernimento';
            case 'jp':
                return '洞察';
        }

        return 'Insight';
    }

    public static function getCampaignsSubMenuTitle(string $language)
    {
        switch ($language) {
            case 'es':
                return 'Campañas';
            case 'de':
                return 'Kampagnen';
            case 'br':
                return 'Campanhas';
            case 'jp':
                return 'キャンペーン';
        }

        return 'Campaigns';
    }

    public static function getExperienceTitle(string $language)
    {
        switch ($language) {
            case 'es':
                return 'Experiencia';
            case 'de':
                return 'Erfahrung';
            case 'br':
                return 'Experiência';
            case 'jp':
                return '経験';
        }

        return 'Experience';
    }

    public static function getLocationsSubMenuTitle(string $language)
    {
        switch ($language) {
            case 'es':
                return 'Ubicaciones';
            case 'de':
                return 'Standorte';
            case 'br':
                return 'Localizações';
            case 'jp':
                return '場所';
        }

        return 'Locations';
    }

    public static function getMarketeersSubMenuTitle(string $language)
    {
        switch ($language) {
            case 'es':
                return 'Partidario del mercado común';
            case 'de':
                return 'Anhänger';
            case 'br':
                return 'Marketeiros';
            case 'jp':
                return 'マーケティング担当者';
        }

        return 'Marketeers';
    }

    public static function getPartnerTitle(string $language)
    {
        switch ($language) {
            case 'es':
                return 'Compañero';
            case 'de':
                return 'Partner';
            case 'br':
                return 'Parceiro';
            case 'jp':
                return 'パートナー';
        }

        return 'Partner';
    }

    public static function getQuotesSubMenuTitle(string $language)
    {
        switch ($language) {
            case 'es':
                return 'Citas';
            case 'de':
                return 'Zitate';
            case 'br':
                return 'Citações';
            case 'jp':
                return '引用';
        }

        return 'Quotes';
    }

    public static function getSubscriptionsSubMenuTitle(string $language)
    {
        switch ($language) {
            case 'es':
                return 'Suscripciones';
            case 'de':
                return 'Abonnements';
            case 'br':
                return 'Assinaturas';
            case 'jp':
                return '定期購読';
        }

        return 'Subscriptions';
    }

    public static function getResourcesTitle(string $language)
    {
        switch ($language) {
            case 'es':
                return 'Recursos';
            case 'de':
                return 'Ressourcen';
            case 'br':
                return 'Recursos';
            case 'jp':
                return 'リソース';
        }

        return 'Resources';
    }

    public static function getChangelogSubMenuTitle(string $language)
    {
        switch ($language) {
            case 'es':
                return 'Registro de cambios';
            case 'de':
                return 'Änderungsprotokoll';
            case 'br':
                return 'Changelog';
            case 'jp':
                return '変更ログ';
        }

        return 'Changelog';
    }

    public static function getSupportSubMenuTitle(string $language)
    {
        switch ($language) {
            case 'es':
                return 'Apoyo';
            case 'de':
                return 'Unterstützung';
            case 'br':
                return 'Apoio';
            case 'jp':
                return 'サポート';
        }

        return 'Support';
    }

    public static function getGeneralSubMenuTitle(string $language)
    {
        switch ($language) {
            case 'es':
                return 'General';
            case 'de':
                return 'Allgemeines';
            case 'br':
                return 'Geral';
            case 'jp':
                return '一般';
        }

        return 'General';
    }

    public static function getBusinessHoursSubMenuTitle(string $language)
    {
        switch ($language) {
            case 'es':
                return 'Horas de trabajo';
            case 'de':
                return 'Öffnungszeiten';
            case 'br':
                return 'Horário comercial';
            case 'jp':
                return '営業時間';
        }

        return 'Business Hours';
    }

    public static function getCaptureSubMenuTitle(string $language)
    {
        switch ($language) {
            case 'es':
                return 'Capturar';
            case 'de':
                return 'Erfassung';
            case 'br':
                return 'Capturar';
            case 'jp':
                return 'キャプチャー';
        }

        return 'Capture';
    }

    public static function getRegistrationsSubMenuTitle(string $language)
    {
        switch ($language) {
            case 'es':
                return 'Registros';
            case 'de':
                return 'Anmeldungen';
            case 'br':
                return 'Inscrições';
            case 'jp':
                return '登録';
        }

        return 'Registrations';
    }

    public static function getPeopleSubMenuTitle(string $language)
    {
        switch ($language) {
            case 'es':
                return 'Gente';
            case 'de':
                return 'Menschen';
            case 'br':
                return 'Pessoas';
            case 'jp':
                return '人';
        }

        return 'People';
    }

    public static function getConnectionsSubMenuTitle(string $language)
    {
        switch ($language) {
            case 'es':
                return 'Conexiones';
            case 'de':
                return 'Verbindungen';
            case 'br':
                return 'Conexões';
            case 'jp':
                return '接続';
        }

        return 'Connections';
    }

    public static function getDevicesSubMenuTitle(string $language)
    {
        switch ($language) {
            case 'es':
                return 'Dispositivos';
            case 'de':
                return 'Geräte';
            case 'br':
                return 'Devices';
            case 'jp':
                return 'デバイス';
        }

        return 'Devices';
    }

    public static function getBandwidthSubMenuTitle(string $language)
    {
        switch ($language) {
            case 'es':
                return 'Ancho de banda';
            case 'de':
                return 'Bandbreite';
            case 'br':
                return 'Largura de banda';
            case 'jp':
                return '帯域幅';
        }

        return 'Bandwidth';
    }

    public static function getDataOptOutSubMenuTitle(string $language)
    {
        switch ($language) {
            case 'es':
                return 'Opción de exclusión de datos';
            case 'de':
                return 'Datenausnahme';
            case 'br':
                return 'Opção de saída de dados';
            case 'jp':
                return 'データオプトアウト';
        }

        return 'Data Opt Out';
    }

    public static function getMarketingOptOutSubMenuTitle(string $language)
    {
        switch ($language) {
            case 'es':
                return 'Opción de exclusión de comercialización';
            case 'de':
                return 'Marketing-Deaktivierung';
            case 'br':
                return 'Opção de marketing';
            case 'jp':
                return 'マーケティングのオプトアウト';
        }

        return 'Marketing Opt Out';
    }

    public static function getUserOriginSubMenuTitle(string $language)
    {
        switch ($language) {
            case 'es':
                return 'Origen del usuario';
            case 'de':
                return 'Benutzerursprung';
            case 'br':
                return 'Origem do usuário';
            case 'jp':
                return 'ユーザー原点';
        }

        return 'User Origin';
    }

    public static function getPaymentsSubMenuTitle(string $language)
    {
        switch ($language) {
            case 'es':
                return 'Pagos';
            case 'de':
                return 'Zahlungen';
            case 'br':
                return 'Pagamentos';
            case 'jp':
                return '支払い';
        }

        return 'Payments';
    }

    public static function getPaymentsSetUpSubMenuTitle(string $language)
    {
        switch ($language) {
            case 'es':
                return 'Configuración de pagos';
            case 'de':
                return 'Zahlungen einrichten';
            case 'br':
                return 'Configuração de pagamentos';
            case 'jp':
                return '支払い設定';
        }

        return 'Payments Setup';
    }

    public static function getConnectedSubMenuTitle(string $language)
    {
        switch ($language) {
            case 'es':
                return 'Conectado';
            case 'de':
                return 'In Verbindung gebracht';
            case 'br':
                return 'Conectado';
            case 'jp':
                return '接続済み';
        }

        return 'Connected';
    }

    public static function getNetworkWifiSubMenuTitle(string $language)
    {
        switch ($language) {
            case 'es':
                return 'Red / Wi-Fi';
            case 'de':
                return 'Netzwerk / Wi-Fi';
            case 'br':
                return 'Rede / Wi-Fi';
            case 'jp':
                return 'ネットワーク/ Wi-Fi';
        }

        return 'Network / Wi-Fi';
    }

    public static function getBrandingSubMenuTitle(string $language)
    {
        switch ($language) {
            case 'es':
                return 'Marca';
            case 'de':
                return 'Brandmarken';
            case 'br':
                return 'Marcar';
            case 'jp':
                return 'ブランディング';
        }

        return 'Branding';
    }

    public static function getCreateCampaignSubMenuTitle(string $language)
    {
        switch ($language) {
            case 'es':
                return 'Crear campaña';
            case 'de':
                return 'Kampagne erstellen';
            case 'br':
                return 'Criar campanha';
            case 'jp':
                return 'キャンペーンの作成';
        }

        return 'Create Campaign';
    }

    public static function getRadiusSetupSubMenuTitle(string $language)
    {
        switch ($language) {
            case 'es':
                return 'Radius Preparar';
            case 'de':
                return 'Radius Konfiguration';
            case 'br':
                return 'Radius Configuração';
            case 'jp':
                return 'Radius セットアップ';
        }

        return 'Radius Setup';
    }

    public static function getVendorConnectionSubMenuTitle(string $language)
    {
        switch ($language) {
            case 'es':
                return 'Conexión';
            case 'de':
                return 'Verbindung';
            case 'br':
                return 'Conexão';
            case 'jp':
                return '接続';
        }

        return 'Connection';
    }

    public static function getSuperSubMenuTitle(string $language)
    {
        switch ($language) {
            case 'es':
                return 'Súper';
            case 'de':
                return 'Super';
            case 'br':
                return 'Super';
            case 'jp':
                return 'スーパー';
        }

        return 'Super';
    }

    public static function getStatusCheckSubMenuTitle(string $language)
    {
        switch ($language) {
            case 'es':
                return 'Comprobación del estado';
            case 'de':
                return 'Statusprüfung';
            case 'br':
                return 'Verificação de status';
            case 'jp':
                return 'ステータスチェック';
        }

        return 'Status Check';
    }

}