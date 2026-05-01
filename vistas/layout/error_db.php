<div style="max-width: 600px; margin: 50px auto; padding: 30px; border: 1px solid #ecc94b; background: #fffff0; font-family: sans-serif; border-radius: 12px; text-align: center; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
    <h2 style="color: #975a16; margin-top: 0;">🛠 Actualización Requerida</h2>
    <p style="color: #744210; line-height: 1.6;">El sistema se ha actualizado pero la base de datos aún no está lista.</p>
    <div style="background: #fff; padding: 15px; border-radius: 8px; border: 1px solid #cbd5e0; margin: 20px 0; font-size: 14px; text-align: left;">
        <strong>Pasos para el administrador:</strong>
        <ol style="margin-top: 10px; padding-left: 20px;">
            <li>Realizar respaldo de la base de datos.</li>
            <li>Ejecutar <code>basededatos_v2_cliente.sql</code>.</li>
            <li>Ejecutar <code>migracion_datos_v2.sql</code>.</li>
        </ol>
    </div>
    <button onclick="location.reload()" style="background: #d69e2e; color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: bold;">Reintentar</button>
</div>
