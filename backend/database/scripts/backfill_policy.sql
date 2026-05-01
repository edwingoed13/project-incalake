-- =====================================================================
-- Backfill standard cancellation policy table into all tour translations
-- Generated 2026-05-01 18:30:01
-- Run in phpMyAdmin against the production DB.
-- Default: only fills rows where policyDescription is empty/missing.
-- To force-overwrite all rows, remove the WHERE block.
-- =====================================================================

-- ES
UPDATE tour_translations tt
JOIN languages l ON l.id = tt.language_id AND UPPER(l.code) = 'ES'
SET tt.booking_texts = JSON_SET(
  COALESCE(tt.booking_texts, JSON_OBJECT()),
  '$.policyDescription',
  '<table class="tiptap-table"><thead><tr><th>Periodo de Anticipación para Anulación</th><th>Penalidad</th><th>Detalles</th></tr></thead><tbody><tr><td>Hasta 48 horas antes del inicio del tour</td><td>20% del total</td><td>Gastos administrativos, comisiones de tarjeta de crédito/débito y otros relacionados.</td></tr><tr><td>Dentro de las 48 horas antes del inicio del tour</td><td>100% del total</td><td>Monto total acordado del servicio.</td></tr></tbody></table>'
)
WHERE
  tt.booking_texts IS NULL
  OR JSON_EXTRACT(tt.booking_texts, '$.policyDescription') IS NULL
  OR JSON_UNQUOTE(JSON_EXTRACT(tt.booking_texts, '$.policyDescription')) = ''
  OR JSON_UNQUOTE(JSON_EXTRACT(tt.booking_texts, '$.policyDescription')) = '<p></p>';

-- EN
UPDATE tour_translations tt
JOIN languages l ON l.id = tt.language_id AND UPPER(l.code) = 'EN'
SET tt.booking_texts = JSON_SET(
  COALESCE(tt.booking_texts, JSON_OBJECT()),
  '$.policyDescription',
  '<table class="tiptap-table"><thead><tr><th>Cancellation Notice Period</th><th>Penalty</th><th>Details</th></tr></thead><tbody><tr><td>Up to 48 hours before the tour starts</td><td>20% of total</td><td>Administrative costs, credit/debit card fees and other related charges.</td></tr><tr><td>Within 48 hours before the tour starts</td><td>100% of total</td><td>Full agreed service amount.</td></tr></tbody></table>'
)
WHERE
  tt.booking_texts IS NULL
  OR JSON_EXTRACT(tt.booking_texts, '$.policyDescription') IS NULL
  OR JSON_UNQUOTE(JSON_EXTRACT(tt.booking_texts, '$.policyDescription')) = ''
  OR JSON_UNQUOTE(JSON_EXTRACT(tt.booking_texts, '$.policyDescription')) = '<p></p>';

-- PT
UPDATE tour_translations tt
JOIN languages l ON l.id = tt.language_id AND UPPER(l.code) = 'PT'
SET tt.booking_texts = JSON_SET(
  COALESCE(tt.booking_texts, JSON_OBJECT()),
  '$.policyDescription',
  '<table class="tiptap-table"><thead><tr><th>Prazo de Antecedência para Cancelamento</th><th>Penalidade</th><th>Detalhes</th></tr></thead><tbody><tr><td>Até 48 horas antes do início do tour</td><td>20% do total</td><td>Despesas administrativas, taxas de cartão de crédito/débito e outras relacionadas.</td></tr><tr><td>Dentro de 48 horas antes do início do tour</td><td>100% do total</td><td>Valor total acordado do serviço.</td></tr></tbody></table>'
)
WHERE
  tt.booking_texts IS NULL
  OR JSON_EXTRACT(tt.booking_texts, '$.policyDescription') IS NULL
  OR JSON_UNQUOTE(JSON_EXTRACT(tt.booking_texts, '$.policyDescription')) = ''
  OR JSON_UNQUOTE(JSON_EXTRACT(tt.booking_texts, '$.policyDescription')) = '<p></p>';

-- FR
UPDATE tour_translations tt
JOIN languages l ON l.id = tt.language_id AND UPPER(l.code) = 'FR'
SET tt.booking_texts = JSON_SET(
  COALESCE(tt.booking_texts, JSON_OBJECT()),
  '$.policyDescription',
  '<table class="tiptap-table"><thead><tr><th>Période d&#039;Anticipation pour Annulation</th><th>Pénalité</th><th>Détails</th></tr></thead><tbody><tr><td>Jusqu&#039;à 48 heures avant le début du tour</td><td>20% du total</td><td>Frais administratifs, commissions de carte de crédit/débit et autres frais associés.</td></tr><tr><td>Dans les 48 heures avant le début du tour</td><td>100% du total</td><td>Montant total convenu du service.</td></tr></tbody></table>'
)
WHERE
  tt.booking_texts IS NULL
  OR JSON_EXTRACT(tt.booking_texts, '$.policyDescription') IS NULL
  OR JSON_UNQUOTE(JSON_EXTRACT(tt.booking_texts, '$.policyDescription')) = ''
  OR JSON_UNQUOTE(JSON_EXTRACT(tt.booking_texts, '$.policyDescription')) = '<p></p>';

-- DE
UPDATE tour_translations tt
JOIN languages l ON l.id = tt.language_id AND UPPER(l.code) = 'DE'
SET tt.booking_texts = JSON_SET(
  COALESCE(tt.booking_texts, JSON_OBJECT()),
  '$.policyDescription',
  '<table class="tiptap-table"><thead><tr><th>Stornierungsfrist</th><th>Gebühr</th><th>Details</th></tr></thead><tbody><tr><td>Bis zu 48 Stunden vor Tourbeginn</td><td>20% des Gesamtbetrags</td><td>Verwaltungskosten, Kredit-/Debitkartengebühren und andere damit verbundene Kosten.</td></tr><tr><td>Innerhalb von 48 Stunden vor Tourbeginn</td><td>100% des Gesamtbetrags</td><td>Vollständig vereinbarter Servicebetrag.</td></tr></tbody></table>'
)
WHERE
  tt.booking_texts IS NULL
  OR JSON_EXTRACT(tt.booking_texts, '$.policyDescription') IS NULL
  OR JSON_UNQUOTE(JSON_EXTRACT(tt.booking_texts, '$.policyDescription')) = ''
  OR JSON_UNQUOTE(JSON_EXTRACT(tt.booking_texts, '$.policyDescription')) = '<p></p>';

-- IT
UPDATE tour_translations tt
JOIN languages l ON l.id = tt.language_id AND UPPER(l.code) = 'IT'
SET tt.booking_texts = JSON_SET(
  COALESCE(tt.booking_texts, JSON_OBJECT()),
  '$.policyDescription',
  '<table class="tiptap-table"><thead><tr><th>Periodo di Preavviso per Annullamento</th><th>Penalità</th><th>Dettagli</th></tr></thead><tbody><tr><td>Fino a 48 ore prima dell&#039;inizio del tour</td><td>20% del totale</td><td>Spese amministrative, commissioni di carta di credito/debito e altre correlate.</td></tr><tr><td>Entro 48 ore prima dell&#039;inizio del tour</td><td>100% del totale</td><td>Importo totale concordato del servizio.</td></tr></tbody></table>'
)
WHERE
  tt.booking_texts IS NULL
  OR JSON_EXTRACT(tt.booking_texts, '$.policyDescription') IS NULL
  OR JSON_UNQUOTE(JSON_EXTRACT(tt.booking_texts, '$.policyDescription')) = ''
  OR JSON_UNQUOTE(JSON_EXTRACT(tt.booking_texts, '$.policyDescription')) = '<p></p>';

-- Verify:
-- SELECT tt.tour_id, l.code, LEFT(JSON_UNQUOTE(JSON_EXTRACT(tt.booking_texts, '$.policyDescription')), 80) AS policy_preview
-- FROM tour_translations tt JOIN languages l ON l.id = tt.language_id LIMIT 12;
