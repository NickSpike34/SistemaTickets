/**
 * JAVASCRIPT GLOBAL Y MANEJO AJAX
 * Sistema de Soporte Técnico tipo Semáforo
 */

$(document).ready(function () {

    // ==========================================================
    // 1. FORMULARIO PÚBLICO DE SOLICITUD DE TICKETS
    // ==========================================================
    if ($('#formNuevoTicket').length > 0) {
        let equipoDetectado = null;

        // Detectar IP automáticamente al cargar la página
        function detectarEquipoPorIP() {
            $('#alertaDeteccion').html('<div class="spinner-border spinner-border-sm text-primary me-2 flex-shrink-0"></div> <div>Detectando configuración de red...</div>').show();

            $.ajax({
                url: 'api/get_equipo.php',
                type: 'GET',
                dataType: 'json',
                success: function (response) {
                    if (response.success) {
                        $('#ip_detectada').val(response.ip);
                        $('#span_ip').text(response.ip);

                        if (response.found && response.data) {
                            const eq = response.data;
                            equipoDetectado = eq;
                            $('#hostname').val(eq.hostname || '');
                            $('#inventario').val(eq.inventario || '');
                            $('#area').val(eq.area || '').prop('readonly', true);

                            // Cargar extensión telefónica automáticamente
                            if (eq.extension) {
                                $('#extension').val(eq.extension);
                                $('#badge_extension').text(eq.extension);
                            } else {
                                $('#badge_extension').text('No registrada');
                            }

                            $('#info_equipo_card').fadeIn();
                            $('#badge_hostname').text(eq.hostname || 'Detectado');
                            $('#badge_inventario').text(eq.inventario || 'N/A');
                            $('#badge_area').text(eq.area);
                            $('#badge_ubicacion').text(eq.ubicacion);

                            $('#alertaDeteccion').removeClass('alert-warning alert-danger').addClass('alert-success')
                                .html('<i class="bi bi-check-circle-fill me-2 fs-5 flex-shrink-0"></i> <div><strong>Equipo identificado:</strong> ' + eq.hostname + ' en ' + eq.area + '</div>');
                        } else {
                            equipoDetectado = null;
                            // IP no registrada en inventario: Pedir Área manualmente
                            $('#area').prop('readonly', false).focus();
                            $('#info_equipo_card').hide();
                            $('#alertaDeteccion').removeClass('alert-success alert-danger').addClass('alert-warning')
                                .html('<i class="bi bi-exclamation-triangle-fill me-2 fs-5 flex-shrink-0"></i> <div>Su dirección IP (' + response.ip + ') no está registrada en inventario. Por favor ingrese su Área manualmente.</div>');
                        }
                    }
                },
                error: function (xhr, status, error) {
                    $('#alertaDeteccion').removeClass('alert-success alert-warning').addClass('alert-danger')
                        .html('<i class="bi bi-x-circle-fill me-2 fs-5 flex-shrink-0"></i> <div>No se pudo consultar la IP automáticamente.</div>');
                }
            });
        }

        detectarEquipoPorIP();

        // Enviar formulario por AJAX
        $('#formNuevoTicket').on('submit', function (e) {
            e.preventDefault();

            const form = $(this);
            const btnSubmit = $('#btnEnviarTicket');
            const originalBtnText = btnSubmit.html();

            // Validar campos requeridos
            if (!$('#area').val().trim()) {
                alert('Por favor ingrese el Área o Departamento.');
                $('#area').focus();
                return;
            }
            if (!$('#problema').val()) {
                alert('Por favor seleccione el tipo de problema.');
                $('#problema').focus();
                return;
            }
            if (!$('#descripcion').val().trim()) {
                alert('Por favor describa brevemente el problema.');
                $('#descripcion').focus();
                return;
            }

            btnSubmit.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Registrando solicitud...');

            $.ajax({
                url: 'api/crear_ticket.php',
                type: 'POST',
                data: form.serialize(),
                dataType: 'json',
                success: function (res) {
                    btnSubmit.prop('disabled', false).html(originalBtnText);

                    if (res.success) {
                        $('#modalFolioTexto').text(res.folio);
                        $('#modalAreaTexto').text(res.data.area);
                        $('#modalProblemaTexto').text(res.data.problema);
                        $('#modalPrioridadTexto').html(res.data.prioridad);

                        // Mostrar modal de confirmación
                        const modalConfirmacion = new bootstrap.Modal(document.getElementById('modalTicketCreado'));
                        modalConfirmacion.show();

                        // Limpiar formulario excepto IP y datos detectados
                        $('#problema').val('');
                        $('#descripcion').val('');
                        if (equipoDetectado && equipoDetectado.extension) {
                            $('#extension').val(equipoDetectado.extension);
                        } else {
                            $('#extension').val('');
                        }
                        $('#prioridad_media').prop('checked', true);
                    } else {
                        alert('Error: ' + res.message);
                    }
                },
                error: function (xhr) {
                    btnSubmit.prop('disabled', false).html(originalBtnText);
                    let msg = 'Ocurrió un error al procesar la solicitud.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        msg = xhr.responseJSON.message;
                    }
                    alert(msg);
                }
            });
        });
    }

    // ==========================================================
    // 2. DASHBOARD PÚBLICO (TV) CON AUTO-REFRESH 12s
    // ==========================================================
    if ($('#dashboardTV').length > 0) {

        let audioNotif = new Audio('https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3');
        let totalPendientesAnterior = null;

        function actualizarReloj() {
            const ahora = new Date();
            const horas = String(ahora.getHours()).padStart(2, '0');
            const minutos = String(ahora.getMinutes()).padStart(2, '0');
            const segundos = String(ahora.getSeconds()).padStart(2, '0');
            $('#relojTV').text(horas + ':' + minutos + ':' + segundos);
        }

        setInterval(actualizarReloj, 1000);
        actualizarReloj();

        function escapeHtml(str) {
            if (!str) return '';
            return $('<div>').text(str).html();
        }

        function cargarDatosDashboard() {
            $('#indicadorCarga').fadeIn(200);

            $.ajax({
                url: '../api/dashboard_data.php',
                type: 'GET',
                dataType: 'json',
                success: function (res) {
                    $('#indicadorCarga').fadeOut(300);

                    if (res.success) {
                        // 1. Actualizar Contadores
                        $('#countPendientes').text(res.contadores.pendientes);
                        $('#countEnProceso').text(res.contadores.en_proceso);
                        $('#countEnEspera').text(res.contadores.en_espera);
                        $('#countAtendidos').text(res.contadores.atendidos_hoy);

                        // Efecto de sonido si llega un nuevo pendiente
                        if (totalPendientesAnterior !== null && res.contadores.pendientes > totalPendientesAnterior) {
                            try { audioNotif.play(); } catch (e) { }
                        }
                        totalPendientesAnterior = res.contadores.pendientes;

                        // 2. Renderizar Tabla de Solicitudes Activas
                        const tbody = $('#tablaTicketsTV tbody');
                        tbody.empty();

                        if (res.tickets.length === 0) {
                            tbody.append(`
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        <i class="bi bi-check-circle" style="font-size: 2.5rem; color: #198754;"></i>
                                        <h4 class="mt-2 text-white">¡No hay solicitudes pendientes en este momento!</h4>
                                        <p class="mb-0 text-secondary">Todos los equipos están operando con normalidad.</p>
                                    </td>
                                </tr>
                            `);
                        } else {
                            res.tickets.forEach(function (t) {
                                const escProblema = escapeHtml(t.problema);
                                const escDescripcion = escapeHtml(t.descripcion);
                                const descHtml = escDescripcion ? `
                                    <div class="mt-2 pt-1 border-top border-secondary border-opacity-25" style="font-size: 0.88rem; line-height: 1.45; color: #cbd5e1; word-break: break-word;">
                                        <i class="bi bi-chat-left-text text-info me-1"></i>${escDescripcion}
                                    </div>
                                ` : '';

                                const row = `
                                    <tr style="border-left: 5px solid ${t.color_estado};">
                                        <td>
                                            <strong class="text-info fs-5">${t.folio}</strong>
                                            <div class="small text-muted">${t.fecha_creacion}</div>
                                        </td>
                                        <td>
                                            <div class="fw-bold">${escapeHtml(t.area)}</div>
                                            <div class="small text-secondary">Ext: ${escapeHtml(t.extension)}</div>
                                        </td>
                                        <td>
                                            <div class="text-light">${escapeHtml(t.equipo)}</div>
                                        </td>
                                        <td>
                                            <div class="fw-bold text-white" style="font-size: 1rem; line-height: 1.35; word-break: break-word;">
                                                ${escProblema}
                                            </div>
                                            ${descHtml}
                                        </td>
                                        <td>
                                            ${t.badge_prioridad}
                                        </td>
                                        <td>
                                            <span class="badge" style="background-color: ${t.color_estado}; color: ${t.estado === 'EN PROCESO' ? '#000' : '#fff'}; font-size: 0.95rem; padding: 6px 14px;">
                                                ${t.estado}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <span class="badge bg-dark border border-secondary text-warning font-monospace fs-6">
                                                <i class="bi bi-stopwatch"></i> ${t.tiempo_transcurrido}
                                            </span>
                                        </td>
                                    </tr>
                                `;
                                tbody.append(row);
                            });
                        }
                    }
                },
                error: function () {
                    $('#indicadorCarga').fadeOut(300);
                }
            });
        }

        // Carga inicial y refresco cada 12 segundos
        cargarDatosDashboard();
        setInterval(cargarDatosDashboard, 12000);
    }

    // ==========================================================
    // 3. ADMINISTRACIÓN: CAMBIO RÁPIDO DE ESTADO (MODAL)
    // ==========================================================
    $(document).on('click', '.btn-cambiar-estado', function () {
        const ticketId = $(this).data('id');
        const folio = $(this).data('folio');
        const estadoActual = $(this).data('estado');

        $('#modal_ticket_id').val(ticketId);
        $('#modal_ticket_folio').text(folio);
        $('#modal_nuevo_estado').val(estadoActual);
        $('#modal_comentario_estado').val('');

        const modal = new bootstrap.Modal(document.getElementById('modalCambiarEstado'));
        modal.show();
    });

    $('#formCambiarEstadoModal').on('submit', function (e) {
        e.preventDefault();

        const form = $(this);
        const btn = form.find('button[type="submit"]');
        const originalText = btn.html();

        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Guardando...');

        $.ajax({
            url: '../api/actualizar_estado.php',
            type: 'POST',
            data: form.serialize(),
            dataType: 'json',
            success: function (res) {
                btn.prop('disabled', false).html(originalText);
                if (res.success) {
                    bootstrap.Modal.getInstance(document.getElementById('modalCambiarEstado')).hide();
                    location.reload();
                } else {
                    alert('Error: ' + res.message);
                }
            },
            error: function (xhr) {
                btn.prop('disabled', false).html(originalText);
                alert(xhr.responseJSON ? xhr.responseJSON.message : 'Error al actualizar el estado.');
            }
        });
    });

    // ==========================================================
    // 4. DETALLE DE TICKET: AGREGAR COMENTARIO Y ARCHIVO
    // ==========================================================
    $('#formAgregarComentario').on('submit', function (e) {
        e.preventDefault();

        const form = this;
        const formData = new FormData(form);
        const btn = $('#btnEnviarComentario');
        const originalText = btn.html();

        if (!$('#texto_comentario').val().trim()) {
            alert('Por favor ingrese un comentario o nota de avance.');
            return;
        }

        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Guardando...');

        $.ajax({
            url: '../api/agregar_comentario.php',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            dataType: 'json',
            success: function (res) {
                btn.prop('disabled', false).html(originalText);
                if (res.success) {
                    $('#texto_comentario').val('');
                    $('#archivo_comentario').val('');

                    const c = res.comentario;
                    let archivoHtml = '';
                    if (c.archivo) {
                        archivoHtml = `<div class="mt-2"><a href="${c.archivo}" target="_blank" class="btn btn-sm btn-outline-primary"><i class="bi bi-paperclip"></i> Ver Evidencia Adjunta</a></div>`;
                    }

                    const nuevoItem = `
                        <li class="timeline-item">
                            <div class="timeline-card">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <strong class="text-primary"><i class="bi bi-person-circle"></i> ${c.autor}</strong>
                                    <small class="text-muted"><i class="bi bi-clock"></i> ${c.fecha_creacion}</small>
                                </div>
                                <p class="mb-0 text-secondary">${c.texto}</p>
                                ${archivoHtml}
                            </div>
                        </li>
                    `;

                    $('#timelineComentarios').prepend(nuevoItem);
                    $('#sinComentariosAlerta').remove();
                } else {
                    alert('Error: ' + res.message);
                }
            },
            error: function (xhr) {
                btn.prop('disabled', false).html(originalText);
                alert(xhr.responseJSON ? xhr.responseJSON.message : 'Error al subir el comentario.');
            }
        });
    });

});
