# DIAGRAMMES

## LOGIN

User → Controller → Application → Domain → Infra → JWT

## REFRESH

Client → /refresh → validate refresh → new JWT

## REVOKE

Admin → increment tokenVersion → invalidate all
