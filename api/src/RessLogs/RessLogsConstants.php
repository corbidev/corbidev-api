<?php

namespace App\RessLogs;

final class RessLogsConstants
{
    public const LOGS_PATH = '/api/logs';
    public const LOGS_ROUTE = 'api_logs_create';

    public const RESPONSE_KEY_ERROR = 'error';
    public const RESPONSE_KEY_ID = 'id';
    public const RESPONSE_KEY_STATUS = 'status';
    public const RESPONSE_STATUS_CREATED = 'created';

    public const FIELD_SOURCE_API_KEY = 'sourceApiKey';
    public const FIELD_SOURCE_ID = 'sourceId';
    public const FIELD_MESSAGE = 'message';
    public const FIELD_TITLE = 'title';
    public const FIELD_URL = 'url';
    public const FIELD_URI = 'uri';
    public const FIELD_HTTP_STATUS = 'httpStatus';
    public const FIELD_DURATION_MS = 'durationMs';
    public const FIELD_FINGERPRINT = 'fingerprint';
    public const FIELD_CONTEXT = 'context';
    public const FIELD_TS = 'ts';
    public const FIELD_CREATED_AT = 'createdAt';
    public const FIELD_LEVEL = 'level';
    public const FIELD_ENV = 'env';
    public const FIELD_URL_ID = 'urlId';
    public const FIELD_URI_ID = 'uriId';
    public const FIELD_ROUTE_ID = 'routeId';
    public const FIELD_TAGS = 'tags';
    public const FIELD_NAME = 'name';
    public const FIELD_API_KEY = 'apiKey';
    public const FIELD_IS_ACTIVE = 'isActive';

    public const ERROR_JSON_INVALID = 'JSON invalide.';
    public const ERROR_JSON_OBJECT_REQUIRED = 'Le corps de la requête doit être un objet JSON.';
    public const ERROR_SOURCE_FIELDS_FORBIDDEN = 'Les champs "sourceApiKey" et "sourceId" ne sont pas acceptés dans le body. Utilisez le header Authorization avec un Bearer JWT valide.';
    public const ERROR_MESSAGE_REQUIRED = 'Le champ «message» est obligatoire.';
    public const ERROR_URL_REQUIRED = 'Le champ «url» est obligatoire.';
    public const ERROR_URL_INVALID = 'Le champ «url» doit être une URL valide (http/https).';
    public const ERROR_SOURCE_NOT_FOUND = 'Source introuvable. Vérifiez votre authentification (Bearer JWT) ou la configuration de la source émettrice.';
    public const ERROR_URL_URI_MISMATCH = 'Le champ "url" ne doit pas contenir une URI différente de celle fournie dans "uri".';
    public const ERROR_URI_URL_CONFLICT = 'Incoherence entre URL et URI: cette URI est deja rattachée a une autre URL.';
    public const ERROR_URI_REQUIRES_URL = 'Une URI doit être rattachée à une URL. Fournissez "url" ou "urlId".';
    public const ERROR_EMPTY_TAG = 'Un tag vide ne peut pas être enregistré.';

    public const ERROR_LEVEL_NOT_FOUND = 'Niveau de log introuvable: %s';
    public const ERROR_ENV_NOT_FOUND = 'Environnement introuvable: %s';
    public const ERROR_URL_ID_NOT_FOUND = 'URL introuvable pour l\'id %s.';
    public const ERROR_URI_ID_NOT_FOUND = 'URI introuvable pour l\'id %s.';
    public const ERROR_TAG_ID_NOT_FOUND = 'Tag introuvable pour l\'id %d.';

    public const DEFAULT_LEVEL_ID = 200;
    public const DEFAULT_ENV_ID = 1;
    public const ALLOWED_URL_SCHEMES = ['http', 'https'];
    public const EMPTY_STRING = '';
    public const URI_PATH_SEPARATOR = '/';
    public const URL_SCHEME_SUFFIX = '://';
    public const URL_USERINFO_SEPARATOR = '@';
    public const URL_PASSWORD_SEPARATOR = ':';
    public const TAG_KEY_ID_FORMAT = 'id:%d';
    public const TAG_KEY_NAME_FORMAT = 'name:%s';

    public const DQL_DELETE_ORPHAN_URLS = 'DELETE FROM App\\RessLogs\\Entity\\LogUrl u WHERE NOT EXISTS (SELECT 1 FROM App\\RessLogs\\Entity\\LogEntry e WHERE e.url = u) AND NOT EXISTS (SELECT 1 FROM App\\RessLogs\\Entity\\LogUri i WHERE i.url = u)';
    public const DQL_DELETE_ORPHAN_URIS = 'DELETE FROM App\\RessLogs\\Entity\\LogUri u WHERE NOT EXISTS (SELECT 1 FROM App\\RessLogs\\Entity\\LogEntry e WHERE e.uri = u)';
}