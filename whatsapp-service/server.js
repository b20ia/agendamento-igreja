import 'dotenv/config';
import { webcrypto } from 'node:crypto';
import express from 'express';
import pino from 'pino';
import qrcode from 'qrcode-terminal';

if (!globalThis.crypto) {
    globalThis.crypto = webcrypto;
}

const app = express();
const logger = pino({ level: process.env.WHATSAPP_LOG_LEVEL || 'info' });
const port = Number(process.env.WHATSAPP_SERVICE_PORT || 3333);
const authToken = process.env.WHATSAPP_SERVICE_TOKEN || '';
let baileys = null;

let socket = null;
let connectionStatus = 'starting';
let lastQrAt = null;
let connectedJid = null;

app.use(express.json());

function requireToken(request, response, next) {
    if (!authToken) {
        return next();
    }

    const header = request.headers.authorization || '';
    const token = header.replace(/^Bearer\s+/i, '');

    if (token !== authToken) {
        return response.status(401).json({ sucesso: false, mensagem: 'Token invalido.' });
    }

    return next();
}

function onlyDigits(value) {
    return String(value || '').replace(/\D/g, '');
}

function formatBrazilianJid(phone) {
    let digits = onlyDigits(phone);

    if (!digits.startsWith('55')) {
        digits = `55${digits}`;
    }

    return `${digits}@s.whatsapp.net`;
}

function brazilianJidCandidates(phone) {
    const digits = onlyDigits(phone);
    const withCountry = digits.startsWith('55') ? digits : `55${digits}`;
    const candidates = [withCountry];

    if (withCountry.length === 13 && withCountry[4] === '9') {
        candidates.push(`${withCountry.slice(0, 4)}${withCountry.slice(5)}`);
    }

    return [...new Set(candidates)].map((candidate) => `${candidate}@s.whatsapp.net`);
}

function normalizeUserJid(jid) {
    return String(jid || '').split('@')[0].split(':')[0];
}

async function resolveWhatsAppJid(phone) {
    const candidates = brazilianJidCandidates(phone);

    if (!socket?.onWhatsApp) {
        return candidates[0];
    }

    const results = await socket.onWhatsApp(...candidates);
    const found = results.find((result) => result.exists && result.jid);

    return found?.jid || candidates[0];
}

async function connectWhatsApp() {
    if (!baileys) {
        const module = await import('baileys');
        baileys = {
            ...module,
            makeWASocket: typeof module.default === 'function'
                ? module.default
                : module.makeWASocket,
        };
    }

    const {
        DisconnectReason,
        fetchLatestBaileysVersion,
        makeWASocket,
        useMultiFileAuthState,
    } = baileys;

    const { state, saveCreds } = await useMultiFileAuthState(
        process.env.WHATSAPP_AUTH_DIR || './storage/whatsapp-auth'
    );
    const { version } = await fetchLatestBaileysVersion();

    socket = makeWASocket({
        auth: state,
        browser: ['Desktop', 'Chrome', '120.0.0'],
        fireInitQueries: false,
        logger,
        markOnlineOnConnect: false,
        printQRInTerminal: false,
        shouldSyncHistoryMessage: () => false,
        syncFullHistory: false,
        version,
    });

    socket.ev.on('creds.update', saveCreds);
    socket.ev.on('connection.update', ({ connection, lastDisconnect, qr }) => {
        if (qr) {
            lastQrAt = new Date().toISOString();
            logger.info('Escaneie o QR Code abaixo com o WhatsApp.');
            qrcode.generate(qr, { small: true });
        }

        if (connection) {
            connectionStatus = connection;
            connectedJid = socket.user?.id || connectedJid;
            logger.info({ connection }, 'Status da conexao WhatsApp');
        }

        if (connection === 'close') {
            const statusCode = lastDisconnect?.error?.output?.statusCode;
            const shouldReconnect = statusCode !== DisconnectReason.loggedOut;

            if (shouldReconnect) {
                setTimeout(connectWhatsApp, 3000);
            } else {
                logger.warn('Sessao encerrada. Apague o diretorio de auth se precisar parear novamente.');
            }
        }
    });
}

app.get('/health', (request, response) => {
    response.json({ ok: true, status: connectionStatus, lastQrAt, connectedJid });
});

app.get('/status', requireToken, (request, response) => {
    response.json({ sucesso: true, status: connectionStatus, lastQrAt });
});

app.post('/send-message', requireToken, async (request, response) => {
    const { phone, message } = request.body || {};

    if (!phone || !message) {
        return response.status(422).json({
            sucesso: false,
            mensagem: 'Informe phone e message.',
        });
    }

    if (!socket || connectionStatus !== 'open') {
        return response.status(503).json({
            sucesso: false,
            mensagem: 'WhatsApp ainda nao esta conectado.',
        });
    }

    try {
        logger.info({ phone }, 'Enviando mensagem pelo WhatsApp');

        const jid = await resolveWhatsAppJid(phone);
        const sendingToConnectedAccount = normalizeUserJid(jid) === normalizeUserJid(socket.user?.id);

        if (sendingToConnectedAccount) {
            logger.warn({
                phone,
                jid,
                connectedJid: socket.user?.id,
            }, 'Destino e o mesmo WhatsApp conectado; o aparelho pode nao mostrar notificacao de recebimento.');
        }

        const result = await socket.sendMessage(jid, { text: message });

        logger.info({
            phone,
            jid,
            id: result?.key?.id || null,
            sendingToConnectedAccount,
        }, 'Mensagem enviada pelo WhatsApp');

        return response.json({
            sucesso: true,
            id: result?.key?.id || null,
            jid,
            aviso: sendingToConnectedAccount
                ? 'Destino e o mesmo WhatsApp conectado; teste com outro numero para receber notificacao.'
                : null,
        });
    } catch (error) {
        logger.error({ error }, 'Erro ao enviar mensagem');

        return response.status(500).json({
            sucesso: false,
            mensagem: 'Erro ao enviar mensagem.',
        });
    }
});

app.listen(port, () => {
    logger.info(`Servico WhatsApp ouvindo na porta ${port}`);
});

connectWhatsApp().catch((error) => {
    connectionStatus = 'error';
    logger.error({
        message: error?.message,
        stack: error?.stack,
    }, 'Erro ao iniciar WhatsApp');
});
