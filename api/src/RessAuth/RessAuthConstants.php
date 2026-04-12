<?php

namespace App\RessAuth;

final class RessAuthConstants
{
    public const TOKEN_TYPE_BEARER = 'Bearer';
    public const AUTHORIZATION_HEADER = 'Authorization';
    public const BEARER_TOKEN_PATTERN = '/^Bearer\s+(.+)$/i';

    public const TOKEN_PATH = '/api/auth/token';
    public const TOKEN_ROUTE = 'api_auth_token_create';
    public const LEGACY_TOKEN_PATH = '/api/logs/token';
    public const LEGACY_TOKEN_ROUTE = 'api_logs_token_create_legacy';

    public const RESPONSE_KEY_ERROR = 'error';
    public const RESPONSE_KEY_TOKEN_TYPE = 'token_type';
    public const RESPONSE_KEY_ACCESS_TOKEN = 'access_token';
    public const RESPONSE_KEY_EXPIRES_IN = 'expires_in';

    public const PAYLOAD_KEY_ACCESS_TOKEN = 'accessToken';
    public const PAYLOAD_KEY_EXPIRES_IN = 'expiresIn';
    public const PAYLOAD_KEY_SOURCE_API_KEY = 'sourceApiKey';
    public const PAYLOAD_KEY_CLIENT_SECRET = 'clientSecret';

    public const ERROR_JSON_INVALID = 'JSON invalide.';
    public const ERROR_JSON_OBJECT_REQUIRED = 'Le corps de la requête doit être un objet JSON.';
    public const ERROR_SOURCE_API_KEY_REQUIRED = 'Le champ "sourceApiKey" est obligatoire.';
    public const ERROR_CLIENT_SECRET_REQUIRED = 'Le champ "clientSecret" est obligatoire.';
    public const ERROR_INVALID_CREDENTIALS = 'Identifiants invalides.';
    public const ERROR_SECRET_NOT_CONFIGURED = 'Aucun secret configure pour cette source. Contactez l\'administrateur.';
    public const ERROR_AUTH_HEADER_SCHEME = 'Le header Authorization doit utiliser le schema Bearer.';
    public const ERROR_EMPTY_JWT = 'Le token JWT est vide.';
    public const ERROR_INVALID_OR_EXPIRED_JWT = 'Token JWT invalide ou expire.';
    public const ERROR_AUTH_HEADER_REQUIRED = 'Le header Authorization avec un Bearer JWT valide est obligatoire.';
    public const DUMMY_PASSWORD_HASH = '$argon2id$v=19$m=65536,t=4,p=1$dummysaltdummysalt$dummyhashvaluedummyhashvaluedummyhashvalue';

    public const SOURCE_CLAIMS = ['sourceApiKey', 'source_api_key', 'apiKey', 'api_key'];
    public const SOURCE_ID_CLAIMS = ['sourceId', 'source_id'];

    public const QUERY_ALIAS_CREDENTIAL = 'credential';
    public const QUERY_CREDENTIAL_API_KEY = 'credential.apiKey = :sourceApiKey';
    public const QUERY_CREDENTIAL_IS_ACTIVE = 'credential.isActive = true';
    public const QUERY_PARAM_SOURCE_API_KEY = 'sourceApiKey';

    public const JWT_CLAIM_ISSUED_AT = 'iat';
    public const JWT_CLAIM_EXPIRES_AT = 'exp';
    public const JWT_CLAIM_SOURCE_API_KEY = 'sourceApiKey';
    public const JWT_CLAIM_SOURCE_ID = 'sourceId';
    public const JWT_CLAIM_SOURCE_NAME = 'sourceName';
    public const JWT_CLAIM_SOURCE_TYPE = 'sourceType';

    public const COMMAND_NAME = 'app:logs:set-source-secret';
    public const COMMAND_DESCRIPTION = 'Crée ou met à jour une source canonique et son clientSecret dans la table auth unique (stocké en Argon2id).';
    public const ARG_SOURCE_API_KEY = 'sourceApiKey';
    public const ARG_SOURCE_API_KEY_DESCRIPTION = 'La sourceApiKey (identifiant public) de la source à configurer.';
    public const OPTION_SECRET = 'secret';
    public const OPTION_SECRET_DESCRIPTION = 'Secret à définir (si omis, un secret aléatoire est généré et affiché une seule fois).';
    public const OPTION_NAME = 'name';
    public const OPTION_NAME_DESCRIPTION = 'Nom de la source si elle doit être créée (par défaut: sourceApiKey).';
    public const OPTION_TYPE = 'type';
    public const OPTION_TYPE_DESCRIPTION = 'Type de la source si elle doit être créée (par défaut: backend).';
    public const OPTION_SHOW = 'show';
    public const OPTION_SHOW_DESCRIPTION = 'Affiche le secret généré en clair après enregistrement (à copier immédiatement, non stocké en clair).';

    public const SOURCE_TYPE_BACKEND = 'backend';
    public const SOURCE_IDENTIFIER_NEW = 'nouvelle';
    public const BOOLEAN_YES = 'oui';
    public const BOOLEAN_NO = 'non';

    public const TITLE_SOURCE = 'Source : %s (id: %s, type: %s, active: %s)';
    public const QUESTION_SECRET = '<info>Secret à définir</info> (laisser vide pour générer automatiquement) : ';
    public const CONSOLE_HELPER_QUESTION = 'question';
    public const ERROR_SECRET_TOO_SHORT = 'Le secret doit comporter au moins 16 caractères.';
    public const SUCCESS_SOURCE_CREATED = 'Source auth créée et secret défini avec succès.';
    public const SUCCESS_SOURCE_UPDATED = 'Secret de la source auth mis à jour avec succès.';
    public const CAUTION_SECRET_CLEAR = 'Secret en clair (à copier immédiatement, il ne sera plus accessible) :';
    public const SECRET_OUTPUT_FORMAT = '  <fg=yellow>%s</>';
}