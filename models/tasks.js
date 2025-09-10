

var mysql = require('mysql'),
    config = require('../config/config'); // файл с настройками бд

// pool - автоматически восстанавливает порванные соединения и позволяет задавать число одновременных соединений (0 - неограниченно)
var pool = mysql.createPool(config);

var Tasks = {
	add: function(values, callback) {
		pool.getConnection(function(err, connection) {
      connection.query('INSERT INTO `chat` SET ?', values, callback); // похоже на execute(), тоже принимает sql-запрос и значения
      connection.release(); // закрытие соединения
      if (err) throw err;
    });
	},
  online: function(values){
    pool.getConnection(function(err, connection) {
      connection.query('update `users` set `online`=? where `id`=?', values);
      connection.release(); // закрытие соединения
      if (err) throw err;
    });
  }
};

module.exports = Tasks; // выносим наш объект наружу