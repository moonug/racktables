{{- define "racktables.name" -}}
{{- default .Chart.Name .Values.nameOverride | trunc 63 | trimSuffix "-" }}
{{- end }}

{{- define "racktables.fullname" -}}
{{- if .Values.fullnameOverride }}
{{- .Values.fullnameOverride | trunc 63 | trimSuffix "-" }}
{{- else }}
{{- $name := default .Chart.Name .Values.nameOverride }}
{{- if contains $name .Release.Name }}
{{- .Release.Name | trunc 63 | trimSuffix "-" }}
{{- else }}
{{- printf "%s-%s" .Release.Name $name | trunc 63 | trimSuffix "-" }}
{{- end }}
{{- end }}
{{- end }}

{{- define "racktables.chart" -}}
{{- printf "%s-%s" .Chart.Name .Chart.Version | replace "+" "_" | trunc 63 | trimSuffix "-" }}
{{- end }}

{{- define "racktables.labels" -}}
helm.sh/chart: {{ include "racktables.chart" . }}
{{ include "racktables.selectorLabels" . }}
app.kubernetes.io/managed-by: {{ .Release.Service }}
{{- end }}

{{- define "racktables.selectorLabels" -}}
app.kubernetes.io/name: {{ include "racktables.name" . }}
app.kubernetes.io/instance: {{ .Release.Name }}
{{- end }}

{{- define "racktables.serviceAccountName" -}}
{{- if .Values.serviceAccount.create }}
{{- default (include "racktables.fullname" .) .Values.serviceAccount.name }}
{{- else }}
{{- default "default" .Values.serviceAccount.name }}
{{- end }}
{{- end }}

{{- define "racktables.image" -}}
{{- printf "%s:%s" .Values.image.repository .Values.image.tag }}
{{- end }}

{{- define "racktables.databaseSecretName" -}}
{{- default (printf "%s-db" (include "racktables.fullname" .)) .Values.externalDatabase.existingSecret }}
{{- end }}

{{- define "racktables.adminEnabled" -}}
{{- if or .Values.admin.password .Values.admin.existingSecret }}true{{ end }}
{{- end }}

{{- define "racktables.adminSecretName" -}}
{{- default (printf "%s-admin" (include "racktables.fullname" .)) .Values.admin.existingSecret }}
{{- end }}

{{- define "racktables.env" -}}
- name: RT_SECRET_FILE
  value: /run/racktables/secret.php
- name: RT_DB_HOST
  value: {{ required "externalDatabase.host is required" .Values.externalDatabase.host | quote }}
- name: RT_DB_PORT
  value: {{ .Values.externalDatabase.port | quote }}
- name: RT_DB_NAME
  value: {{ required "externalDatabase.name is required" .Values.externalDatabase.name | quote }}
- name: RT_DB_USER
  value: {{ required "externalDatabase.user is required" .Values.externalDatabase.user | quote }}
- name: RT_DB_PASSWORD_FILE
  value: /run/secrets/racktables/db-password
{{- if include "racktables.adminEnabled" . }}
- name: RT_ADMIN_PASSWORD_FILE
  value: /run/secrets/racktables/admin-password
{{- end }}
{{- end }}

{{- define "racktables.secretVolumes" -}}
- name: database-secret
  secret:
    secretName: {{ include "racktables.databaseSecretName" . }}
{{- if include "racktables.adminEnabled" . }}
- name: admin-secret
  secret:
    secretName: {{ include "racktables.adminSecretName" . }}
{{- end }}
{{- end }}

{{- define "racktables.secretVolumeMounts" -}}
- name: database-secret
  mountPath: /run/secrets/racktables/db-password
  subPath: {{ .Values.externalDatabase.passwordKey }}
  readOnly: true
{{- if include "racktables.adminEnabled" . }}
- name: admin-secret
  mountPath: /run/secrets/racktables/admin-password
  subPath: {{ .Values.admin.passwordKey }}
  readOnly: true
{{- end }}
{{- end }}
