flowchart TD

  ROOT["LogEvent"]

  %% Champs principaux
  ROOT --> ts["ts : string (ISO8601)\n⚠ fallback = now()"]
  ROOT --> level["level : string\n⚠ obligatoire\n⚠ fallback = INFO"]
  ROOT --> level_int["level_int : int\n⚠ dérivé"]
  ROOT --> message["message : string\n🔥 obligatoire"]
  ROOT --> source["source : string"]
  ROOT --> channel["channel : string"]
  ROOT --> env["env : string\n⚠ fallback = prod"]
  ROOT --> host["host : string"]
  ROOT --> ip["ip : string"]
  ROOT --> fingerprint["fingerprint : string\n⚠ auto-généré"]
  ROOT --> tags["tags : array<string>\n⚠ fallback = []"]

  %% Origin
  ROOT --> origin["origin : object"]

  origin --> origin_url["url : string\n⚠ fallback = unknown"]
  origin --> origin_uri["uri : string\n⚠ fallback = /"]
  origin --> origin_method["method : string\n⚠ fallback = CLI"]
  origin --> origin_client["client : string\n⚠ fallback = unknown"]
  origin --> origin_version["version : string\n⚠ fallback = unknown"]

  %% HTTP
  ROOT --> http["http : object"]

  http --> http_method["method : string"]
  http --> http_url["url : string"]
  http --> http_route["route : string"]
  http --> http_status["status : int"]
  http --> http_duration["duration_ms : int"]
  http --> http_ua["user_agent : string"]

  %% User
  ROOT --> user["user : object"]

  user --> user_id["id : int"]
  user --> user_session["session_id : string"]

  %% Trace
  ROOT --> trace["trace : object"]

  trace --> trace_req["request_id : string\n⚠ auto si absent"]
  trace --> trace_corr["correlation_id : string"]

  %% Error
  ROOT --> error["error : object"]

  error --> error_code["code : string"]
  error --> error_class["exception_class : string"]
  error --> error_file["file : string"]
  error --> error_line["line : int"]

  %% Context / Extra
  ROOT --> context["context : object\n⚠ max 10KB"]
  ROOT --> extra["extra : object\n⚠ max 10KB"]