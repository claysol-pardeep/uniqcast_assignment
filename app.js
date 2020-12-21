const express = require('express');
const bodyParser = require('body-parser');
const router = express.Router();
const debug = require('debug')('uniquecast:server');
const path = require('path');
const multer = require('multer');
const serveIndex = require('serve-index');
const fs = require('fs');
const app = express();
const NATS = require('nats');
const servers = ['nats://nats:4222', 'nats://nats:8222', 'nats://nats:6222'];
var ffmpeg = require('fluent-ffmpeg');
const nc = NATS.connect({ servers: servers });
//const nc = NATS.connect();
nc.on('connect', () => {
	console.log('Connected to ' + nc.currentServer.url.host);
});

// parse application/x-www-form-urlencoded
app.use(bodyParser.urlencoded({ extended: false }));

var storage = multer.diskStorage({
	destination: (req, file, cb) => {
		cb(null, './public/');
	},
	filename: (req, file, cb) => {
		cb(null, file.fieldname + '-' + Date.now() + path.extname(file.originalname));
	},
});

const upload = multer({ storage: storage });

//get the router
app.use(express.json());
app.use(express.urlencoded({ extended: false }));

app.use('/ftp', express.static('frontend'), serveIndex('public', { icons: true }));
app.post('/segment', function (req, res) {
	return ffmpeg.ffprobe('uploads/1.mp4', function (err, metadata) {
		return res.send(metadata);
	});
});
app.get('/', function (req, res) {
	nc.publish('video', 'file data processing.');
	return res.send('Welcome in UniqueCast Assignment!');
});
nc.subscribe('record', function (msg) {
	console.log('Received a message: ' + msg);
});
// NATS Subscribe the Channel for testing the receive message.
nc.subscribe('video', function (msg) {
	console.log('Received a message: ' + msg);
	ffmpeg.ffprobe('uploads/1.mp4', function (err, metadata) {
		nc.publish('record', JSON.stringify(metadata));
	});
});

/* Micro-service that upload the file in docker mounted directory and return the file information */
app.post('/upload', upload.single('file'), function (req, res) {
	let url = req.hostname + ':3000/ftp/assets/' + req.file.filename;
	nc.publish('video-channel', url);
	return res.send(req.file);
});

/**** Outdated MP4 Processing with FFMPEG file processing ****/
router.post('/video', (req, res) => {
	let source = req.body.file;
	console.log(`source url is : ${source}`);
	let target = '/Users/apple/assets/' + Date.now() + '.mp4';
	fs.copyFile(source, target, (err) => {
		if (err) throw err;

		fs.stat(target, (err, stat) => {
			// Handle file not found
			if (err !== null && err.code === 'ENOENT') {
				res.sendStatus(404);
			}
			const fileSize = stat.size;
			const range = req.headers.range;
			if (range) {
				const parts = range.replace(/bytes=/, '').split('-');

				const start = parseInt(parts[0], 10);
				const end = parts[1] ? parseInt(parts[1], 10) : fileSize - 1;

				const chunksize = end - start + 1;
				const file = fs.createReadStream(target, { start, end });
				const head = {
					'Content-Range': `bytes ${start}-${end}/${fileSize}`,
					'Accept-Ranges': 'bytes',
					'Content-Length': chunksize,
					'Content-Type': 'video/mp4',
				};
				// nc.publish('video-channel', chunksize);
				res.writeHead(206, head);
				file.pipe(res);
			} else {
				const head = {
					'Content-Length': fileSize,
					'Content-Type': 'video/mp4',
				};
				nc.publish('channel', target);
				res.writeHead(200, head);
				fs.createReadStream(target).pipe(res);
				///nc.unsubscribe(sid);
			}
		});
	});
});

app.use(router);
const PORT = 3000;

app.listen(PORT, () => {
	console.log(`Server listening on port ${PORT}`);
});
