SET FOREIGN_KEY_CHECKS=0;

--
-- Table structure for table `activity_logs`
--

DROP TABLE IF EXISTS `activity_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `idx_action` (`action`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_logs`
--

LOCK TABLES `activity_logs` WRITE;
/*!40000 ALTER TABLE `activity_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `activity_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ai_screening_config`
--

DROP TABLE IF EXISTS `ai_screening_config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai_screening_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `job_title` varchar(255) NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `required_skills` text DEFAULT NULL,
  `priority_skills` text DEFAULT NULL,
  `min_experience` int(11) DEFAULT 0,
  `preferred_education` varchar(255) DEFAULT NULL,
  `auto_shortlist_score` int(11) DEFAULT 80,
  `auto_reject_score` int(11) DEFAULT 40,
  `interview_date` datetime DEFAULT NULL,
  `interview_location` varchar(255) DEFAULT NULL,
  `interview_type` enum('physical','online') DEFAULT 'physical',
  `meeting_link` varchar(500) DEFAULT NULL,
  `interviewer_name` varchar(200) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  KEY `idx_active` (`is_active`),
  KEY `idx_dates` (`start_date`,`end_date`),
  CONSTRAINT `ai_screening_config_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai_screening_config`
--

LOCK TABLES `ai_screening_config` WRITE;
/*!40000 ALTER TABLE `ai_screening_config` DISABLE KEYS */;
INSERT INTO `ai_screening_config` VALUES (1,'soft','IT','2026-06-30','2026-07-01','javascript,laravel','javascript,laravel',3,'Bsc in cse',80,40,'2026-07-03 10:30:00','Mirpur-2,dhaka','physical','','manager','',0,7,'2026-06-30 04:33:35','2026-07-02 19:35:00'),(2,'soft','IT','2026-07-02','2026-07-05','php,mysql','mysql,php',0,'',80,40,'2026-07-02 17:21:00','Mirpur-2,dhaka','physical','','manager','cv',0,7,'2026-07-02 11:21:46','2026-07-02 19:35:00'),(3,'soft','IT','2026-07-02','2026-07-02','java','java,mysql',0,'Bsc in cse',80,40,'2026-07-02 21:15:00','Mirpur-2,dhaka','physical','','manager','CV',0,7,'2026-07-02 15:16:01','2026-07-02 19:35:00'),(4,'soft','IT','2026-07-02','2026-07-02','java','java',0,'',80,40,'2026-07-03 21:34:00','Mirpur-2,dhaka','physical','','','cv',0,7,'2026-07-02 15:34:36','2026-07-02 19:35:00'),(5,'software engineering','IT','2026-07-03','2026-07-04','html,css,java,javascript,competitive programming','java',0,'',80,40,'2026-07-20 03:11:00','Mirpur-2,dhaka','physical','','manager','',0,7,'2026-07-02 19:11:35','2026-07-02 19:35:00'),(6,'software engineering','HR','2026-07-03','2026-08-02','PHP, MySQL, HTML, CSS, JavaScript','PHP, MySQL',0,'Bsc in cse',50,40,'2026-07-03 01:18:00','Mirpur-2,dhaka','physical','','manager','bring your cv',1,7,'2026-07-02 19:18:51','2026-07-04 03:57:41'),(7,'software engineering','IT','2026-07-03','2026-07-03','java,html,css','competitve prgramming',0,'Bsc in cse',80,40,'2026-07-10 01:29:00','Mirpur-2,dhaka','physical','','manager','cv',0,7,'2026-07-02 19:29:40','2026-07-02 19:35:00'),(8,'soft','IT','2026-07-03','2026-07-10','java,html,css','java',0,'',80,40,'2026-07-15 01:36:00','Mirpur-2,dhaka','physical','','manager','asd',0,7,'2026-07-02 19:36:46','2026-07-02 19:59:45'),(9,'mnarketing manager','Marketing','2026-07-04','2026-07-06','Php,javascript,mysql,javascript','javascript',0,'',50,10,'2026-07-10 09:35:00','Mirpur-2,dhaka','physical','','manager','Bring Your All Pepar',1,7,'2026-07-04 03:36:16','2026-07-04 03:36:16'),(10,'mnarketing manager','Marketing','2026-07-04','2026-07-06','React.js, Node.js, Express.js, MongoDB, JavaScript, HTML, CSS, Tailwind CSS, Bootstrap','React.js, Node.js, MongoDB',0,'Bsc in cse',50,10,'2026-07-04 09:45:00','Mirpur-2,dhaka','physical','','manager','Bring Your cv',1,7,'2026-07-04 03:45:53','2026-07-04 05:12:12'),(11,'software engineering','IT','2026-07-04','2026-07-06','Html,css,bootstrap,java,mysql','java',0,'Bsc in cse',80,40,'2026-07-15 12:55:00','Mirpur-2,dhaka','physical','','manager','Bring your All Pepar',1,7,'2026-07-04 06:55:52','2026-07-04 06:55:52'),(12,'Manager','HR','2026-07-10','2026-07-11','php,mysql','php',0,'',80,40,'2026-07-13 14:48:00','Mirpur-2,dhaka','physical','','manager','Bring your All pepar',1,7,'2026-07-10 08:49:07','2026-07-10 08:49:07');
/*!40000 ALTER TABLE `ai_screening_config` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ai_screening_results`
--

DROP TABLE IF EXISTS `ai_screening_results`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai_screening_results` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `config_id` int(11) NOT NULL,
  `candidate_name` varchar(200) NOT NULL,
  `candidate_email` varchar(255) NOT NULL,
  `candidate_phone` varchar(20) DEFAULT NULL,
  `resume_text` longtext DEFAULT NULL,
  `resume_file_path` varchar(500) DEFAULT NULL,
  `match_score` decimal(5,2) DEFAULT 0.00,
  `skills_found` text DEFAULT NULL,
  `missing_skills` text DEFAULT NULL,
  `priority_skills_found` text DEFAULT NULL,
  `experience_match` varchar(50) DEFAULT NULL,
  `education_match` varchar(50) DEFAULT NULL,
  `summary` text DEFAULT NULL,
  `recommendation` enum('shortlist','review','reject') DEFAULT 'review',
  `status` enum('pending','screened','shortlisted','rejected','interview_scheduled') DEFAULT 'pending',
  `interview_sent` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_config_id` (`config_id`),
  KEY `idx_status` (`status`),
  KEY `idx_recommendation` (`recommendation`),
  CONSTRAINT `ai_screening_results_ibfk_1` FOREIGN KEY (`config_id`) REFERENCES `ai_screening_config` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=42 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai_screening_results`
--

LOCK TABLES `ai_screening_results` WRITE;
/*!40000 ALTER TABLE `ai_screening_results` DISABLE KEYS */;
INSERT INTO `ai_screening_results` VALUES (25,10,'Shamim Reja','abdulalim528260@gmail.com','01957123090','pdftotext: /opt/lampp/lib/libstdc++.so.6: version `GLIBCXX_3.4.21\' not found (required by pdftotext)\npdftotext: /opt/lampp/lib/libstdc++.so.6: version `CXXABI_1.3.9\' not found (required by pdftotext)\npdftotext: /opt/lampp/lib/libstdc++.so.6: version `GLIBCXX_3.4.20\' not found (required by pdftotext)\npdftotext: /opt/lampp/lib/libstdc++.so.6: version `GLIBCXX_3.4.32\' not found (required by /lib/x86_64-linux-gnu/libpoppler.so.134)\npdftotext: /opt/lampp/lib/libstdc++.so.6: version `GLIBCXX_3.4.20\' not found (required by /lib/x86_64-linux-gnu/libpoppler.so.134)\npdftotext: /opt/lampp/lib/libstdc++.so.6: version `CXXABI_1.3.9\' not found (required by /lib/x86_64-linux-gnu/libpoppler.so.134)\npdftotext: /opt/lampp/lib/libstdc++.so.6: version `GLIBCXX_3.4.29\' not found (required by /lib/x86_64-linux-gnu/libpoppler.so.134)\npdftotext: /opt/lampp/lib/libstdc++.so.6: version `GLIBCXX_3.4.21\' not found (required by /lib/x86_64-linux-gnu/libpoppler.so.134)\npdftotext: /opt/lampp/lib/libstdc++.so.6: version `GLIBCXX_3.4.21\' not found (required by /lib/x86_64-linux-gnu/libLerc.so.4)\npdftotext: /opt/lampp/lib/libstdc++.so.6: version `CXXABI_1.3.9\' not found (required by /lib/x86_64-linux-gnu/libLerc.so.4)\npdftotext: /opt/lampp/lib/libstdc++.so.6: version `GLIBCXX_3.4.29\' not found (required by /lib/x86_64-linux-gnu/libLerc.so.4)\n','uploads/resumes/1783137813_MD. Sifat Bhuyan.pdf',0.00,'','php','',NULL,NULL,'Resume does not contain any relevant skills or experience. Unable to extract information due to technical issues.','reject','screened',0,'2026-07-04 04:03:33','2026-07-04 04:03:33'),(26,10,'alim','abdulalim528260@gmail.com','01957123090','pdftotext: /opt/lampp/lib/libstdc++.so.6: version `GLIBCXX_3.4.21\' not found (required by pdftotext)\npdftotext: /opt/lampp/lib/libstdc++.so.6: version `CXXABI_1.3.9\' not found (required by pdftotext)\npdftotext: /opt/lampp/lib/libstdc++.so.6: version `GLIBCXX_3.4.20\' not found (required by pdftotext)\npdftotext: /opt/lampp/lib/libstdc++.so.6: version `GLIBCXX_3.4.32\' not found (required by /lib/x86_64-linux-gnu/libpoppler.so.134)\npdftotext: /opt/lampp/lib/libstdc++.so.6: version `GLIBCXX_3.4.20\' not found (required by /lib/x86_64-linux-gnu/libpoppler.so.134)\npdftotext: /opt/lampp/lib/libstdc++.so.6: version `CXXABI_1.3.9\' not found (required by /lib/x86_64-linux-gnu/libpoppler.so.134)\npdftotext: /opt/lampp/lib/libstdc++.so.6: version `GLIBCXX_3.4.29\' not found (required by /lib/x86_64-linux-gnu/libpoppler.so.134)\npdftotext: /opt/lampp/lib/libstdc++.so.6: version `GLIBCXX_3.4.21\' not found (required by /lib/x86_64-linux-gnu/libpoppler.so.134)\npdftotext: /opt/lampp/lib/libstdc++.so.6: version `GLIBCXX_3.4.21\' not found (required by /lib/x86_64-linux-gnu/libLerc.so.4)\npdftotext: /opt/lampp/lib/libstdc++.so.6: version `CXXABI_1.3.9\' not found (required by /lib/x86_64-linux-gnu/libLerc.so.4)\npdftotext: /opt/lampp/lib/libstdc++.so.6: version `GLIBCXX_3.4.29\' not found (required by /lib/x86_64-linux-gnu/libLerc.so.4)\n','uploads/resumes/1783139316_MD. Sifat Bhuyan.pdf',0.00,'','php','',NULL,NULL,'Resume does not contain any relevant skills or experience. Unable to extract information due to technical issues.','reject','screened',0,'2026-07-04 04:28:36','2026-07-04 04:28:37'),(27,10,'sojib','abdulalim528260@gmail.com','01957123090','Abdul Alim\nBangladesh  |  your.email@example.com |  +8801XXXXXXXXX  |  https://github.com/yourusername |  Codeforces: \nhttps://codeforces.com/profile/yourhandle |  https://linkedin.com/in/yourprofile\nSUMMARY\nMotivated and passionate Computer Science and Engineering student with a strong interest in software development \nand problem-solving. Experienced in Competitive \0\nopportunity to apply my programming skills, learn new technologies, and contribute to innovative software projects.\nEDUCATION\nUniversity of Global Village (UGV)\nBachelor of Science (B.Sc.) in Computer Science & Engineering, Current CGPA: 2.59Expected Graduation: 2027\nPROJECTS\nEmployee Management System | Technologies: HTML, CSS, JavaScript, PHP, MySQL\n•Developed a web-based Employee Management System.\n•Implemented employee registration, update, delete, and search functionalities.\n•Used MySQL for database management.\nTECHNICAL SKILLS\nJava\nJavaScript\nHTML5\nCSS3\nGit & GitHub\nProblem Solving\nObject-Oriented Programming (OOP)\nCOMPETITIVE PROGRAMMING\n- Solved 225+ programming problems. - Regular participant in online programming contests. - Familiar with \nalgorithms and data structures.\nSOFT SKILLS\n- Problem Solving - Teamwork - Communication - Quick Learner - Time Management\nLANGUAGES\n- Bengali (Native) - English (Intermediate)\nINTERESTS\n- Software Development - Competitive Programming - Web Development - Learning New Technologies','uploads/resumes/1783139450_test.pdf',50.00,'Manual review needed',NULL,NULL,NULL,NULL,'AI screening fallback','review','screened',0,'2026-07-04 04:30:50','2026-07-04 04:30:52'),(28,10,'shimul islam','abdulalim528260@gmail.com','01957123090','MD. Sifat Bhuyan\nabdulalim528260@gmail.com |  +8801957123090\nSUMMARY\nMotivated Computer Science and Engineering (CSE) student with a strong interest in Software \0\nDevelopment, and Competitive Programming. Experienced in developing academic web applications using PHP, \nMySQL, HTML, CSS, and JavaScript. Passionate about learning new technologies and solving real-world \nprogramming problems.\nEDUCATION\nBachelor of Science (B.Sc.) in Computer Science & Engineering	2023 – 2027 (Expected)\nPROJECTS\nEmployee Management System | Technologies: PHP, MySQL, HTML, CSS, JavaScript\n•Developed a full-stack Employee Management System.\n•Implemented secure user authentication and authorization.\n•Performed CRUD (Create, Read, Update, Delete) operations for employee records.\n•Designed responsive user interfaces.\n•Connected frontend with MySQL database using PHP.\nTECHNICAL SKILLS\nJava\nJavaScript\nPHP\nPython\nHTML5\nCSS3\nJavaScript\nMySQL\nGit\nGitHub\nVisual Studio Code\nObject-Oriented Programming (OOP)\nData Structures & Algorithms\nCompetitive Programming\nProblem Solving\nProblem Solving\nTeamwork\nQuick Learning\nCommunication\nTime Management\nAnalytical Thinking\nCOMPETITIVE PROGRAMMING\n\n- Solved 225+ programming problems on Codeforces. - Practicing \0\nExperience with Graph Algorithms, Number Theory, BFS, DFS, Dijkstra, MST, and Dynamic Programming. - \nParticipated in online programming contests.\nLANGUAGES\n- Bengali (Native) - English (Professional Working Proficiency)','uploads/resumes/1783139895_MD. Sifat Bhuyan.pdf',100.00,'PHP, MySQL, HTML, CSS, JavaScript',NULL,NULL,NULL,NULL,'Manual score calculation','shortlist','screened',0,'2026-07-04 04:38:15','2026-07-04 04:38:16'),(29,10,'merjasourov','abdulalim528260@gmail.com','01957123090','‌Jan 2023 - Running‌B.Sc. in Computer Science and Engineering‌\nBangladesh University of Business & Technology‌\nCGPA : 3.51‌\nEDUCATION‌\nTECHNICAL SKILL‌\nProgramming Language : ‌JavaScript, Typescript, C++, Python‌\nFrameworks & Libaries‌    ‌:‌ React, Node, Express, Taliwind, MongoDB‌\nOthers‌ : Git, Linux‌Mirpur-2, Dhaka-1216‌ +880 1719100265‌ merjashourov@gmail.com‌ \nI am a Front-End Developer who loves making clean, responsive, and user-friendly websites. I work\nwith HTML, CSS, JavaScript, and React to build modern web pages. I enjoy turning ideas into simple\nand interactive digital designs that people can use easily.‌\nJuly 2025-‌  ‌Running‌News Portal Website | ‌code‌\nCurrently Woking on Porject.‌\nThis is a news website built using the MERN stack (MongoDB, Express, React, Node.js). The\nwebsite allows users to view news articles, search for articles by keyword, and create an\naccount to save articles for later reading.‌\nPROJECT‌Awards/Activities: \nPlaced 11 among 85 participants in BUBT Intra-University Programming Contest 2024\nHonorable Mention among 2400 teams ICPC DHAKA REGIONAL SITE ONLINE PRELIMINARY\nCONTEST 2023\nMax rating 823 in Codeforces. \nADDITIONAL INFORMATION\nSUMMARY‌\n ‌Nev 2023 - Dec 2023‌\nHangman X NumberGussing Game | code\nSoftware Development Project I ( Varsity 1 Year Project )\nst\nHangman involves guessing a hidden word by suggesting letters, with incorrect guesses\nleading to the gradual drawing of a \"hangman\" figure. The Number Guessing game\nchallenges players to guess a randomly generated number within a set range, with feedback\nprovided on whether their guess is too high or too low. \nProgramming Language: C++\nMERJA SHOUROV‌\nFORNT-END DEVELOPER‌\nGithubLindedIn	Portfolio','uploads/resumes/1783140768_merjaShourov.pdf',0.00,'PHP, MySQL, HTML, CSS, JavaScript',NULL,NULL,NULL,NULL,'AI unavailable - Manual calculation used','reject','screened',0,'2026-07-04 04:52:48','2026-07-04 04:52:49'),(30,10,'MD. Sifat Bhuyan','abdulalim528260@gmail.com','01957123090','‌Jan 2023 - Running‌B.Sc. in Computer Science and Engineering‌\nBangladesh University of Business & Technology‌\nCGPA : 3.51‌\nEDUCATION‌\nTECHNICAL SKILL‌\nProgramming Language : ‌JavaScript, Typescript, C++, Python‌\nFrameworks & Libaries‌    ‌:‌ React, Node, Express, Taliwind, MongoDB‌\nOthers‌ : Git, Linux‌Mirpur-2, Dhaka-1216‌ +880 1719100265‌ merjashourov@gmail.com‌ \nI am a Front-End Developer who loves making clean, responsive, and user-friendly websites. I work\nwith HTML, CSS, JavaScript, and React to build modern web pages. I enjoy turning ideas into simple\nand interactive digital designs that people can use easily.‌\nJuly 2025-‌  ‌Running‌News Portal Website | ‌code‌\nCurrently Woking on Porject.‌\nThis is a news website built using the MERN stack (MongoDB, Express, React, Node.js). The\nwebsite allows users to view news articles, search for articles by keyword, and create an\naccount to save articles for later reading.‌\nPROJECT‌Awards/Activities: \nPlaced 11 among 85 participants in BUBT Intra-University Programming Contest 2024\nHonorable Mention among 2400 teams ICPC DHAKA REGIONAL SITE ONLINE PRELIMINARY\nCONTEST 2023\nMax rating 823 in Codeforces. \nADDITIONAL INFORMATION\nSUMMARY‌\n ‌Nev 2023 - Dec 2023‌\nHangman X NumberGussing Game | code\nSoftware Development Project I ( Varsity 1 Year Project )\nst\nHangman involves guessing a hidden word by suggesting letters, with incorrect guesses\nleading to the gradual drawing of a \"hangman\" figure. The Number Guessing game\nchallenges players to guess a randomly generated number within a set range, with feedback\nprovided on whether their guess is too high or too low. \nProgramming Language: C++\nMERJA SHOUROV‌\nFORNT-END DEVELOPER‌\nGithubLindedIn	Portfolio','uploads/resumes/1783141301_merjaShourov.pdf',0.00,'php',NULL,NULL,NULL,NULL,'Manual calculation used','reject','screened',0,'2026-07-04 05:01:42','2026-07-04 05:01:42'),(31,10,'MD. Sifat Bhuyan','abdulalim528260@gmail.com','01957123090','ABU SIAM \nMERN STACK DEVELOPER \nJamalpur, Bangladesh : Phone: +880 1960112553 \nEmail | LinkedIn | GitHub | Portfolio \n \nObjective \nI have a skill in full-stack web development. I have completed some full-stack projects with \nReact.js, Node.js, Express.js and MongoDB. Seeking a role as a web developer so I can use my \nthorough knowledge of programming frameworks and development software. \nSkills and Technologies \n Expertise: HTML, CSS, JavaScript, Tailwind CSS, Bootstrap, React Bootstrap, Daisy UI, React.js, \nNode.js, Express.js, MongoDB, REST API \n Comfortable: C, C++, Java, Material UI, MySQL, Axios \n Familiar: Next JS, Redux, Data Structure, Stripe \n Tools: Git, GitHub, VS Code, Chrome Devtool, Firebase, Netlify, Vercel, Figma, Xampp \nProjects \n1. Kitchen Food Services - Cloud Kitchen – Live Link | GitHub Client | GitHub Server \nTechnologies: React, Tailwind CSS, Firebase, Node, Express, MongoDB,  \n Firebase authentication with google, email, and password \n After login, the user can add services and can give reviews for each service. \n My Reviews route will show the login user\'s reviews. Users can edit or delete this review. \n2. Mobile Mart – Used Mobile Resell – Live Link | GitHub Client | GitHub Server \n Technologies: React.js, Tailwind CSS, Firebase, Express.js, Node.js, MongoDB, Stripe \n Users can log in seller account or buyer account with firebase authentication. \n User role-based dashboard, payment system and add product. \n Used Node.js for server, MongoDB for database and stripe for payment. \n3. Coding Era - Online course - Live Link | GitHub Client | GitHub Server \nTechnologies: React, Tailwind CSS, Firebase, Node, Express, MongoDB \n Firebase authentication with google, email, and password \n The course page shows all courses and a sidebar shows all course titles. \n Users can see course details and buy this course.  \nEducation \nBSc Engineering in Computer Science and Engineering  (CGPA 3.56) \nFaridpur Engineering College, Faridpur.                   (2018-Present) \nRelated Course \n Web Development Course at Programming Hero \n Mobile Game and Application Development at ICT Division \nLanguage \n Bangla - Native \n English - Comfortable','uploads/resumes/1783141599_1670507073791.pdf',0.00,'php',NULL,NULL,NULL,NULL,'Manual calculation used','reject','screened',0,'2026-07-04 05:06:39','2026-07-04 05:06:40'),(32,10,'MD. Sifat Bhuyan','abdulalim528260@gmail.com','01957123090','ABU SIAM \nMERN STACK DEVELOPER \nJamalpur, Bangladesh : Phone: +880 1960112553 \nEmail | LinkedIn | GitHub | Portfolio \n \nObjective \nI have a skill in full-stack web development. I have completed some full-stack projects with \nReact.js, Node.js, Express.js and MongoDB. Seeking a role as a web developer so I can use my \nthorough knowledge of programming frameworks and development software. \nSkills and Technologies \n Expertise: HTML, CSS, JavaScript, Tailwind CSS, Bootstrap, React Bootstrap, Daisy UI, React.js, \nNode.js, Express.js, MongoDB, REST API \n Comfortable: C, C++, Java, Material UI, MySQL, Axios \n Familiar: Next JS, Redux, Data Structure, Stripe \n Tools: Git, GitHub, VS Code, Chrome Devtool, Firebase, Netlify, Vercel, Figma, Xampp \nProjects \n1. Kitchen Food Services - Cloud Kitchen – Live Link | GitHub Client | GitHub Server \nTechnologies: React, Tailwind CSS, Firebase, Node, Express, MongoDB,  \n Firebase authentication with google, email, and password \n After login, the user can add services and can give reviews for each service. \n My Reviews route will show the login user\'s reviews. Users can edit or delete this review. \n2. Mobile Mart – Used Mobile Resell – Live Link | GitHub Client | GitHub Server \n Technologies: React.js, Tailwind CSS, Firebase, Express.js, Node.js, MongoDB, Stripe \n Users can log in seller account or buyer account with firebase authentication. \n User role-based dashboard, payment system and add product. \n Used Node.js for server, MongoDB for database and stripe for payment. \n3. Coding Era - Online course - Live Link | GitHub Client | GitHub Server \nTechnologies: React, Tailwind CSS, Firebase, Node, Express, MongoDB \n Firebase authentication with google, email, and password \n The course page shows all courses and a sidebar shows all course titles. \n Users can see course details and buy this course.  \nEducation \nBSc Engineering in Computer Science and Engineering  (CGPA 3.56) \nFaridpur Engineering College, Faridpur.                   (2018-Present) \nRelated Course \n Web Development Course at Programming Hero \n Mobile Game and Application Development at ICT Division \nLanguage \n Bangla - Native \n English - Comfortable','uploads/resumes/1783141948_1670507073791.pdf',90.00,'Array','Array','Array',NULL,NULL,'The candidate has a strong background in full-stack web development with expertise in the required skills, including React.js, Node.js, Express.js, and MongoDB.','shortlist','screened',0,'2026-07-04 05:12:28','2026-07-04 05:12:29'),(33,10,'merja sourov','abdulalim01708gailc@gmail.com','01957123090','ABU SIAM \nMERN STACK DEVELOPER \nJamalpur, Bangladesh : Phone: +880 1960112553 \nEmail | LinkedIn | GitHub | Portfolio \n \nObjective \nI have a skill in full-stack web development. I have completed some full-stack projects with \nReact.js, Node.js, Express.js and MongoDB. Seeking a role as a web developer so I can use my \nthorough knowledge of programming frameworks and development software. \nSkills and Technologies \n Expertise: HTML, CSS, JavaScript, Tailwind CSS, Bootstrap, React Bootstrap, Daisy UI, React.js, \nNode.js, Express.js, MongoDB, REST API \n Comfortable: C, C++, Java, Material UI, MySQL, Axios \n Familiar: Next JS, Redux, Data Structure, Stripe \n Tools: Git, GitHub, VS Code, Chrome Devtool, Firebase, Netlify, Vercel, Figma, Xampp \nProjects \n1. Kitchen Food Services - Cloud Kitchen – Live Link | GitHub Client | GitHub Server \nTechnologies: React, Tailwind CSS, Firebase, Node, Express, MongoDB,  \n Firebase authentication with google, email, and password \n After login, the user can add services and can give reviews for each service. \n My Reviews route will show the login user\'s reviews. Users can edit or delete this review. \n2. Mobile Mart – Used Mobile Resell – Live Link | GitHub Client | GitHub Server \n Technologies: React.js, Tailwind CSS, Firebase, Express.js, Node.js, MongoDB, Stripe \n Users can log in seller account or buyer account with firebase authentication. \n User role-based dashboard, payment system and add product. \n Used Node.js for server, MongoDB for database and stripe for payment. \n3. Coding Era - Online course - Live Link | GitHub Client | GitHub Server \nTechnologies: React, Tailwind CSS, Firebase, Node, Express, MongoDB \n Firebase authentication with google, email, and password \n The course page shows all courses and a sidebar shows all course titles. \n Users can see course details and buy this course.  \nEducation \nBSc Engineering in Computer Science and Engineering  (CGPA 3.56) \nFaridpur Engineering College, Faridpur.                   (2018-Present) \nRelated Course \n Web Development Course at Programming Hero \n Mobile Game and Application Development at ICT Division \nLanguage \n Bangla - Native \n English - Comfortable','uploads/resumes/1783146684_1670507073791.pdf',90.00,'Array','Array','',NULL,NULL,'The candidate has a strong background in full-stack web development with expertise in all required skills.','shortlist','screened',0,'2026-07-04 06:31:24','2026-07-04 06:31:29'),(34,10,'107_Abdul Alim','abdulalim01708gailc@gmail.com','01957123090','ABU SIAM \nMERN STACK DEVELOPER \nJamalpur, Bangladesh : Phone: +880 1960112553 \nEmail | LinkedIn | GitHub | Portfolio \n \nObjective \nI have a skill in full-stack web development. I have completed some full-stack projects with \nReact.js, Node.js, Express.js and MongoDB. Seeking a role as a web developer so I can use my \nthorough knowledge of programming frameworks and development software. \nSkills and Technologies \n Expertise: HTML, CSS, JavaScript, Tailwind CSS, Bootstrap, React Bootstrap, Daisy UI, React.js, \nNode.js, Express.js, MongoDB, REST API \n Comfortable: C, C++, Java, Material UI, MySQL, Axios \n Familiar: Next JS, Redux, Data Structure, Stripe \n Tools: Git, GitHub, VS Code, Chrome Devtool, Firebase, Netlify, Vercel, Figma, Xampp \nProjects \n1. Kitchen Food Services - Cloud Kitchen – Live Link | GitHub Client | GitHub Server \nTechnologies: React, Tailwind CSS, Firebase, Node, Express, MongoDB,  \n Firebase authentication with google, email, and password \n After login, the user can add services and can give reviews for each service. \n My Reviews route will show the login user\'s reviews. Users can edit or delete this review. \n2. Mobile Mart – Used Mobile Resell – Live Link | GitHub Client | GitHub Server \n Technologies: React.js, Tailwind CSS, Firebase, Express.js, Node.js, MongoDB, Stripe \n Users can log in seller account or buyer account with firebase authentication. \n User role-based dashboard, payment system and add product. \n Used Node.js for server, MongoDB for database and stripe for payment. \n3. Coding Era - Online course - Live Link | GitHub Client | GitHub Server \nTechnologies: React, Tailwind CSS, Firebase, Node, Express, MongoDB \n Firebase authentication with google, email, and password \n The course page shows all courses and a sidebar shows all course titles. \n Users can see course details and buy this course.  \nEducation \nBSc Engineering in Computer Science and Engineering  (CGPA 3.56) \nFaridpur Engineering College, Faridpur.                   (2018-Present) \nRelated Course \n Web Development Course at Programming Hero \n Mobile Game and Application Development at ICT Division \nLanguage \n Bangla - Native \n English - Comfortable','uploads/resumes/1783147261_1670507073791.pdf',90.00,'Array','Array','',NULL,NULL,'The candidate has a strong background in full-stack web development with expertise in all the required skills.','shortlist','screened',0,'2026-07-04 06:41:01','2026-07-04 06:41:06'),(35,11,'MD. Sifat Bhuyan','abdulalim01708gailc@gmail.com','01704281617','ABU SIAM \nMERN STACK DEVELOPER \nJamalpur, Bangladesh : Phone: +880 1960112553 \nEmail | LinkedIn | GitHub | Portfolio \n \nObjective \nI have a skill in full-stack web development. I have completed some full-stack projects with \nReact.js, Node.js, Express.js and MongoDB. Seeking a role as a web developer so I can use my \nthorough knowledge of programming frameworks and development software. \nSkills and Technologies \n Expertise: HTML, CSS, JavaScript, Tailwind CSS, Bootstrap, React Bootstrap, Daisy UI, React.js, \nNode.js, Express.js, MongoDB, REST API \n Comfortable: C, C++, Java, Material UI, MySQL, Axios \n Familiar: Next JS, Redux, Data Structure, Stripe \n Tools: Git, GitHub, VS Code, Chrome Devtool, Firebase, Netlify, Vercel, Figma, Xampp \nProjects \n1. Kitchen Food Services - Cloud Kitchen – Live Link | GitHub Client | GitHub Server \nTechnologies: React, Tailwind CSS, Firebase, Node, Express, MongoDB,  \n Firebase authentication with google, email, and password \n After login, the user can add services and can give reviews for each service. \n My Reviews route will show the login user\'s reviews. Users can edit or delete this review. \n2. Mobile Mart – Used Mobile Resell – Live Link | GitHub Client | GitHub Server \n Technologies: React.js, Tailwind CSS, Firebase, Express.js, Node.js, MongoDB, Stripe \n Users can log in seller account or buyer account with firebase authentication. \n User role-based dashboard, payment system and add product. \n Used Node.js for server, MongoDB for database and stripe for payment. \n3. Coding Era - Online course - Live Link | GitHub Client | GitHub Server \nTechnologies: React, Tailwind CSS, Firebase, Node, Express, MongoDB \n Firebase authentication with google, email, and password \n The course page shows all courses and a sidebar shows all course titles. \n Users can see course details and buy this course.  \nEducation \nBSc Engineering in Computer Science and Engineering  (CGPA 3.56) \nFaridpur Engineering College, Faridpur.                   (2018-Present) \nRelated Course \n Web Development Course at Programming Hero \n Mobile Game and Application Development at ICT Division \nLanguage \n Bangla - Native \n English - Comfortable','uploads/resumes/1783148209_1670507073791.pdf',85.00,'Array','Array','',NULL,NULL,'The candidate has a strong background in full-stack web development with skills in HTML, CSS, Bootstrap, Java, and MySQL, making them a suitable fit for the software engineering role.','shortlist','interview_scheduled',1,'2026-07-04 06:56:49','2026-07-04 06:57:58'),(36,11,'Shamim Reja','mdshamimreja353@gmail.com','01952826095','ABU SIAM \nMERN STACK DEVELOPER \nJamalpur, Bangladesh : Phone: +880 1960112553 \nEmail | LinkedIn | GitHub | Portfolio \n \nObjective \nI have a skill in full-stack web development. I have completed some full-stack projects with \nReact.js, Node.js, Express.js and MongoDB. Seeking a role as a web developer so I can use my \nthorough knowledge of programming frameworks and development software. \nSkills and Technologies \n Expertise: HTML, CSS, JavaScript, Tailwind CSS, Bootstrap, React Bootstrap, Daisy UI, React.js, \nNode.js, Express.js, MongoDB, REST API \n Comfortable: C, C++, Java, Material UI, MySQL, Axios \n Familiar: Next JS, Redux, Data Structure, Stripe \n Tools: Git, GitHub, VS Code, Chrome Devtool, Firebase, Netlify, Vercel, Figma, Xampp \nProjects \n1. Kitchen Food Services - Cloud Kitchen – Live Link | GitHub Client | GitHub Server \nTechnologies: React, Tailwind CSS, Firebase, Node, Express, MongoDB,  \n Firebase authentication with google, email, and password \n After login, the user can add services and can give reviews for each service. \n My Reviews route will show the login user\'s reviews. Users can edit or delete this review. \n2. Mobile Mart – Used Mobile Resell – Live Link | GitHub Client | GitHub Server \n Technologies: React.js, Tailwind CSS, Firebase, Express.js, Node.js, MongoDB, Stripe \n Users can log in seller account or buyer account with firebase authentication. \n User role-based dashboard, payment system and add product. \n Used Node.js for server, MongoDB for database and stripe for payment. \n3. Coding Era - Online course - Live Link | GitHub Client | GitHub Server \nTechnologies: React, Tailwind CSS, Firebase, Node, Express, MongoDB \n Firebase authentication with google, email, and password \n The course page shows all courses and a sidebar shows all course titles. \n Users can see course details and buy this course.  \nEducation \nBSc Engineering in Computer Science and Engineering  (CGPA 3.56) \nFaridpur Engineering College, Faridpur.                   (2018-Present) \nRelated Course \n Web Development Course at Programming Hero \n Mobile Game and Application Development at ICT Division \nLanguage \n Bangla - Native \n English - Comfortable','uploads/resumes/1783148757_1670507073791.pdf',85.00,'Array','Array','',NULL,NULL,'The candidate has a strong background in full-stack web development and possesses all the required skills for the software engineering position.','shortlist','screened',0,'2026-07-04 07:05:57','2026-07-04 07:06:02'),(37,11,'synthis fedouse','jannat.ferdouse370@gmail.com','019999043586','ABU SIAM \nMERN STACK DEVELOPER \nJamalpur, Bangladesh : Phone: +880 1960112553 \nEmail | LinkedIn | GitHub | Portfolio \n \nObjective \nI have a skill in full-stack web development. I have completed some full-stack projects with \nReact.js, Node.js, Express.js and MongoDB. Seeking a role as a web developer so I can use my \nthorough knowledge of programming frameworks and development software. \nSkills and Technologies \n Expertise: HTML, CSS, JavaScript, Tailwind CSS, Bootstrap, React Bootstrap, Daisy UI, React.js, \nNode.js, Express.js, MongoDB, REST API \n Comfortable: C, C++, Java, Material UI, MySQL, Axios \n Familiar: Next JS, Redux, Data Structure, Stripe \n Tools: Git, GitHub, VS Code, Chrome Devtool, Firebase, Netlify, Vercel, Figma, Xampp \nProjects \n1. Kitchen Food Services - Cloud Kitchen – Live Link | GitHub Client | GitHub Server \nTechnologies: React, Tailwind CSS, Firebase, Node, Express, MongoDB,  \n Firebase authentication with google, email, and password \n After login, the user can add services and can give reviews for each service. \n My Reviews route will show the login user\'s reviews. Users can edit or delete this review. \n2. Mobile Mart – Used Mobile Resell – Live Link | GitHub Client | GitHub Server \n Technologies: React.js, Tailwind CSS, Firebase, Express.js, Node.js, MongoDB, Stripe \n Users can log in seller account or buyer account with firebase authentication. \n User role-based dashboard, payment system and add product. \n Used Node.js for server, MongoDB for database and stripe for payment. \n3. Coding Era - Online course - Live Link | GitHub Client | GitHub Server \nTechnologies: React, Tailwind CSS, Firebase, Node, Express, MongoDB \n Firebase authentication with google, email, and password \n The course page shows all courses and a sidebar shows all course titles. \n Users can see course details and buy this course.  \nEducation \nBSc Engineering in Computer Science and Engineering  (CGPA 3.56) \nFaridpur Engineering College, Faridpur.                   (2018-Present) \nRelated Course \n Web Development Course at Programming Hero \n Mobile Game and Application Development at ICT Division \nLanguage \n Bangla - Native \n English - Comfortable','uploads/resumes/1783149569_1670507073791.pdf',80.00,'Html,css,bootstrap,java,mysql','','Html,css,bootstrap,java,mysql',NULL,NULL,'Strong candidate with relevant skills in html, css, bootstrap, java and mysql','shortlist','interview_scheduled',1,'2026-07-04 07:19:29','2026-07-04 07:22:12'),(38,11,'synthia biswas','janant.ferdouse370@gmail.com','019999043586','ABU SIAM \nMERN STACK DEVELOPER \nJamalpur, Bangladesh : Phone: +880 1960112553 \nEmail | LinkedIn | GitHub | Portfolio \n \nObjective \nI have a skill in full-stack web development. I have completed some full-stack projects with \nReact.js, Node.js, Express.js and MongoDB. Seeking a role as a web developer so I can use my \nthorough knowledge of programming frameworks and development software. \nSkills and Technologies \n Expertise: HTML, CSS, JavaScript, Tailwind CSS, Bootstrap, React Bootstrap, Daisy UI, React.js, \nNode.js, Express.js, MongoDB, REST API \n Comfortable: C, C++, Java, Material UI, MySQL, Axios \n Familiar: Next JS, Redux, Data Structure, Stripe \n Tools: Git, GitHub, VS Code, Chrome Devtool, Firebase, Netlify, Vercel, Figma, Xampp \nProjects \n1. Kitchen Food Services - Cloud Kitchen – Live Link | GitHub Client | GitHub Server \nTechnologies: React, Tailwind CSS, Firebase, Node, Express, MongoDB,  \n Firebase authentication with google, email, and password \n After login, the user can add services and can give reviews for each service. \n My Reviews route will show the login user\'s reviews. Users can edit or delete this review. \n2. Mobile Mart – Used Mobile Resell – Live Link | GitHub Client | GitHub Server \n Technologies: React.js, Tailwind CSS, Firebase, Express.js, Node.js, MongoDB, Stripe \n Users can log in seller account or buyer account with firebase authentication. \n User role-based dashboard, payment system and add product. \n Used Node.js for server, MongoDB for database and stripe for payment. \n3. Coding Era - Online course - Live Link | GitHub Client | GitHub Server \nTechnologies: React, Tailwind CSS, Firebase, Node, Express, MongoDB \n Firebase authentication with google, email, and password \n The course page shows all courses and a sidebar shows all course titles. \n Users can see course details and buy this course.  \nEducation \nBSc Engineering in Computer Science and Engineering  (CGPA 3.56) \nFaridpur Engineering College, Faridpur.                   (2018-Present) \nRelated Course \n Web Development Course at Programming Hero \n Mobile Game and Application Development at ICT Division \nLanguage \n Bangla - Native \n English - Comfortable','uploads/resumes/1783151619_1670507073791.pdf',70.00,'Html,css,bootstrap,java,mysql','','Html,css,bootstrap,java,mysql',NULL,NULL,'Strong candidate with most required skills','shortlist','screened',0,'2026-07-04 07:53:40','2026-07-04 07:53:45'),(39,11,'Merja Sourov ','merjashourov@gmail.com','01957123090','ABU SIAM \nMERN STACK DEVELOPER \nJamalpur, Bangladesh : Phone: +880 1960112553 \nEmail | LinkedIn | GitHub | Portfolio \n \nObjective \nI have a skill in full-stack web development. I have completed some full-stack projects with \nReact.js, Node.js, Express.js and MongoDB. Seeking a role as a web developer so I can use my \nthorough knowledge of programming frameworks and development software. \nSkills and Technologies \n Expertise: HTML, CSS, JavaScript, Tailwind CSS, Bootstrap, React Bootstrap, Daisy UI, React.js, \nNode.js, Express.js, MongoDB, REST API \n Comfortable: C, C++, Java, Material UI, MySQL, Axios \n Familiar: Next JS, Redux, Data Structure, Stripe \n Tools: Git, GitHub, VS Code, Chrome Devtool, Firebase, Netlify, Vercel, Figma, Xampp \nProjects \n1. Kitchen Food Services - Cloud Kitchen – Live Link | GitHub Client | GitHub Server \nTechnologies: React, Tailwind CSS, Firebase, Node, Express, MongoDB,  \n Firebase authentication with google, email, and password \n After login, the user can add services and can give reviews for each service. \n My Reviews route will show the login user\'s reviews. Users can edit or delete this review. \n2. Mobile Mart – Used Mobile Resell – Live Link | GitHub Client | GitHub Server \n Technologies: React.js, Tailwind CSS, Firebase, Express.js, Node.js, MongoDB, Stripe \n Users can log in seller account or buyer account with firebase authentication. \n User role-based dashboard, payment system and add product. \n Used Node.js for server, MongoDB for database and stripe for payment. \n3. Coding Era - Online course - Live Link | GitHub Client | GitHub Server \nTechnologies: React, Tailwind CSS, Firebase, Node, Express, MongoDB \n Firebase authentication with google, email, and password \n The course page shows all courses and a sidebar shows all course titles. \n Users can see course details and buy this course.  \nEducation \nBSc Engineering in Computer Science and Engineering  (CGPA 3.56) \nFaridpur Engineering College, Faridpur.                   (2018-Present) \nRelated Course \n Web Development Course at Programming Hero \n Mobile Game and Application Development at ICT Division \nLanguage \n Bangla - Native \n English - Comfortable','uploads/resumes/1783226496_1670507073791.pdf',70.00,'Html,css,bootstrap,java,mysql','','Html,css,bootstrap,java,mysql',NULL,NULL,'Strong candidate with most required skills','shortlist','screened',0,'2026-07-05 04:41:36','2026-07-05 04:41:41'),(40,6,'Alim','abdulalim01708gailc@gmail.com','01957123090','ABU SIAM \nMERN STACK DEVELOPER \nJamalpur, Bangladesh : Phone: +880 1960112553 \nEmail | LinkedIn | GitHub | Portfolio \n \nObjective \nI have a skill in full-stack web development. I have completed some full-stack projects with \nReact.js, Node.js, Express.js and MongoDB. Seeking a role as a web developer so I can use my \nthorough knowledge of programming frameworks and development software. \nSkills and Technologies \n Expertise: HTML, CSS, JavaScript, Tailwind CSS, Bootstrap, React Bootstrap, Daisy UI, React.js, \nNode.js, Express.js, MongoDB, REST API \n Comfortable: C, C++, Java, Material UI, MySQL, Axios \n Familiar: Next JS, Redux, Data Structure, Stripe \n Tools: Git, GitHub, VS Code, Chrome Devtool, Firebase, Netlify, Vercel, Figma, Xampp \nProjects \n1. Kitchen Food Services - Cloud Kitchen – Live Link | GitHub Client | GitHub Server \nTechnologies: React, Tailwind CSS, Firebase, Node, Express, MongoDB,  \n Firebase authentication with google, email, and password \n After login, the user can add services and can give reviews for each service. \n My Reviews route will show the login user\'s reviews. Users can edit or delete this review. \n2. Mobile Mart – Used Mobile Resell – Live Link | GitHub Client | GitHub Server \n Technologies: React.js, Tailwind CSS, Firebase, Express.js, Node.js, MongoDB, Stripe \n Users can log in seller account or buyer account with firebase authentication. \n User role-based dashboard, payment system and add product. \n Used Node.js for server, MongoDB for database and stripe for payment. \n3. Coding Era - Online course - Live Link | GitHub Client | GitHub Server \nTechnologies: React, Tailwind CSS, Firebase, Node, Express, MongoDB \n Firebase authentication with google, email, and password \n The course page shows all courses and a sidebar shows all course titles. \n Users can see course details and buy this course.  \nEducation \nBSc Engineering in Computer Science and Engineering  (CGPA 3.56) \nFaridpur Engineering College, Faridpur.                   (2018-Present) \nRelated Course \n Web Development Course at Programming Hero \n Mobile Game and Application Development at ICT Division \nLanguage \n Bangla - Native \n English - Comfortable','uploads/resumes/1783521195_1670507073791.pdf',0.00,NULL,NULL,NULL,NULL,NULL,NULL,'review','pending',0,'2026-07-08 14:33:15','2026-07-08 14:33:15'),(41,12,'Alim','abdulalim01708gailc@gmail.com','01957123090','ABU SIAM \nMERN STACK DEVELOPER \nJamalpur, Bangladesh : Phone: +880 1960112553 \nEmail | LinkedIn | GitHub | Portfolio \n \nObjective \nI have a skill in full-stack web development. I have completed some full-stack projects with \nReact.js, Node.js, Express.js and MongoDB. Seeking a role as a web developer so I can use my \nthorough knowledge of programming frameworks and development software. \nSkills and Technologies \n Expertise: HTML, CSS, JavaScript, Tailwind CSS, Bootstrap, React Bootstrap, Daisy UI, React.js, \nNode.js, Express.js, MongoDB, REST API \n Comfortable: C, C++, Java, Material UI, MySQL, Axios \n Familiar: Next JS, Redux, Data Structure, Stripe \n Tools: Git, GitHub, VS Code, Chrome Devtool, Firebase, Netlify, Vercel, Figma, Xampp \nProjects \n1. Kitchen Food Services - Cloud Kitchen – Live Link | GitHub Client | GitHub Server \nTechnologies: React, Tailwind CSS, Firebase, Node, Express, MongoDB,  \n Firebase authentication with google, email, and password \n After login, the user can add services and can give reviews for each service. \n My Reviews route will show the login user\'s reviews. Users can edit or delete this review. \n2. Mobile Mart – Used Mobile Resell – Live Link | GitHub Client | GitHub Server \n Technologies: React.js, Tailwind CSS, Firebase, Express.js, Node.js, MongoDB, Stripe \n Users can log in seller account or buyer account with firebase authentication. \n User role-based dashboard, payment system and add product. \n Used Node.js for server, MongoDB for database and stripe for payment. \n3. Coding Era - Online course - Live Link | GitHub Client | GitHub Server \nTechnologies: React, Tailwind CSS, Firebase, Node, Express, MongoDB \n Firebase authentication with google, email, and password \n The course page shows all courses and a sidebar shows all course titles. \n Users can see course details and buy this course.  \nEducation \nBSc Engineering in Computer Science and Engineering  (CGPA 3.56) \nFaridpur Engineering College, Faridpur.                   (2018-Present) \nRelated Course \n Web Development Course at Programming Hero \n Mobile Game and Application Development at ICT Division \nLanguage \n Bangla - Native \n English - Comfortable','uploads/resumes/1783673399_1670507073791.pdf',50.00,'php,mysql',NULL,NULL,NULL,NULL,'Manual calculation','review','screened',0,'2026-07-10 08:49:59','2026-07-10 08:50:03');
/*!40000 ALTER TABLE `ai_screening_results` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `attendance`
--

DROP TABLE IF EXISTS `attendance`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `attendance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `attendance_date` date NOT NULL,
  `check_in` time DEFAULT NULL,
  `check_out` time DEFAULT NULL,
  `total_hours` decimal(5,2) DEFAULT NULL,
  `status` enum('present','absent','late','half_day') DEFAULT 'present',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_attendance` (`employee_id`,`attendance_date`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `attendance`
--

LOCK TABLES `attendance` WRITE;
/*!40000 ALTER TABLE `attendance` DISABLE KEYS */;
INSERT INTO `attendance` VALUES (1,5,'2026-06-13','18:18:17','18:18:21',NULL,'present','2026-06-13 16:18:17','2026-06-13 16:18:21'),(2,6,'2026-06-13','19:33:52','19:33:58',NULL,'present','2026-06-13 17:33:52','2026-06-13 17:33:58'),(3,6,'2026-06-15','08:42:01','08:42:20',NULL,'present','2026-06-15 06:42:01','2026-06-15 06:42:20'),(4,15,'2026-06-15','16:30:41','16:31:23',NULL,'present','2026-06-15 14:30:41','2026-06-15 14:31:23'),(5,6,'2026-06-21','20:01:27',NULL,NULL,'present','2026-06-21 18:01:27','2026-06-21 18:01:27'),(6,16,'2026-06-22','05:25:29','05:29:36',NULL,'present','2026-06-22 03:25:29','2026-06-22 03:29:36'),(7,6,'2026-06-22','05:31:10','10:56:06',NULL,'present','2026-06-22 03:31:10','2026-06-22 08:56:06'),(8,15,'2026-06-22','07:50:18',NULL,NULL,'present','2026-06-22 05:50:18','2026-06-22 05:50:18'),(9,7,'2026-06-22','10:13:00','10:13:10',NULL,'late','2026-06-22 08:13:00','2026-06-22 08:13:10'),(10,6,'2026-06-23','13:42:04','13:42:16',NULL,'late','2026-06-23 11:42:04','2026-06-23 11:42:16'),(11,6,'2026-06-24','09:18:30','13:31:36',NULL,'present','2026-06-24 07:18:30','2026-06-24 07:31:36'),(12,6,'2026-06-25','20:44:09',NULL,NULL,'late','2026-06-25 14:44:09','2026-06-25 14:44:09'),(13,6,'2026-06-26','09:31:08',NULL,NULL,'present','2026-06-26 03:31:08','2026-06-26 03:31:08'),(14,6,'2026-06-28','21:04:01','21:11:16',NULL,'late','2026-06-28 15:04:01','2026-06-28 15:11:16'),(15,6,'2026-06-29','21:47:18','21:50:24',NULL,'late','2026-06-29 15:47:18','2026-06-29 15:50:24'),(16,27,'2026-06-30','08:38:39',NULL,NULL,'present','2026-06-30 02:38:39','2026-06-30 02:38:39'),(17,6,'2026-06-30','08:44:04','15:43:47',NULL,'present','2026-06-30 02:44:04','2026-06-30 09:43:47'),(18,6,'2026-07-04','13:42:14',NULL,NULL,'late','2026-07-04 07:42:14','2026-07-04 07:42:14'),(19,6,'2026-07-05','14:40:40',NULL,NULL,'late','2026-07-05 08:40:40','2026-07-05 08:40:40'),(20,6,'2026-07-09','18:46:52',NULL,NULL,'late','2026-07-09 12:46:52','2026-07-09 12:46:52');
/*!40000 ALTER TABLE `attendance` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `company_settings`
--

DROP TABLE IF EXISTS `company_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `company_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_name` varchar(200) DEFAULT NULL,
  `company_logo` varchar(500) DEFAULT NULL,
  `company_address` text DEFAULT NULL,
  `company_phone` varchar(20) DEFAULT NULL,
  `company_email` varchar(255) DEFAULT NULL,
  `company_website` varchar(200) DEFAULT NULL,
  `tax_id` varchar(100) DEFAULT NULL,
  `fiscal_year_start` date DEFAULT NULL,
  `fiscal_year_end` date DEFAULT NULL,
  `leave_policy` text DEFAULT NULL,
  `holiday_calendar` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `payroll_config` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `company_settings`
--

LOCK TABLES `company_settings` WRITE;
/*!40000 ALTER TABLE `company_settings` DISABLE KEYS */;
INSERT INTO `company_settings` VALUES (1,'KormoShathi HR Solutions',NULL,'Dhaka, Bangladesh','+880123456789','info@kormoshathi.com',NULL,NULL,NULL,NULL,NULL,NULL,'2026-06-15 08:12:50','{\"house_rent_percent\":\"50\",\"medical_percent\":\"10\",\"travel_percent\":\"5\",\"pf_percent\":\"10\",\"tax_percent\":\"5\"}'),(2,'KormoShathi HR Solutions',NULL,'Dhaka, Bangladesh','+880123456789','info@kormoshathi.com',NULL,NULL,NULL,NULL,NULL,NULL,'2026-06-15 07:46:27','{\"house_rent_percent\":50,\"medical_percent\":10,\"travel_percent\":5,\"pf_percent\":10,\"tax_percent\":5}');
/*!40000 ALTER TABLE `company_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employee_submissions`
--

DROP TABLE IF EXISTS `employee_submissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `employee_submissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `file_size` int(11) DEFAULT NULL,
  `status` enum('pending','reviewed','approved','rejected') DEFAULT 'pending',
  `feedback` text DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `reviewed_by` (`reviewed_by`),
  KEY `idx_status` (`status`),
  KEY `idx_employee_id` (`employee_id`),
  CONSTRAINT `employee_submissions_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `employee_submissions_ibfk_2` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employee_submissions`
--

LOCK TABLES `employee_submissions` WRITE;
/*!40000 ALTER TABLE `employee_submissions` DISABLE KEYS */;
INSERT INTO `employee_submissions` VALUES (1,6,'sf','sdfdsa','uploads/submissions/submission_6_1782754033.png','features.png',216773,'reviewed','not good.please wrrite again and submit me at 12pm',7,'2026-06-29 17:36:16','2026-06-29 17:27:13','2026-06-29 17:36:16'),(2,6,'for check','','uploads/submissions/submission_6_1782756802.pdf','An_Automated_Resume_Screening_System_Usi.pdf',538949,'approved','Good job!!',7,'2026-06-29 18:14:20','2026-06-29 18:13:22','2026-06-29 18:14:20'),(3,6,'asd','','uploads/submissions/submission_6_1782757145.pdf','1-s2.0-S2666285X21001011-main.pdf',1099674,'approved','good job\n',7,'2026-06-29 18:20:06','2026-06-29 18:19:05','2026-06-29 18:20:06'),(4,27,'Review Cv','hello sir..!\r\ni read a cv but i dont understand that cv','uploads/submissions/submission_27_1782786864.pdf','Abdul Alim (1).pdf',112094,'pending','not good cv.that not enogh skill for our company ',7,'2026-06-30 02:35:52','2026-06-30 02:34:24','2026-06-30 02:35:52'),(5,6,'new task','asd','uploads/submissions/submission_6_1783150926.pdf','1-s2.0-S187705092030750X-main.pdf',777276,'reviewed','good job',7,'2026-07-05 09:23:25','2026-07-04 07:42:06','2026-07-05 09:23:25');
/*!40000 ALTER TABLE `employee_submissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `interview_schedules`
--

DROP TABLE IF EXISTS `interview_schedules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `interview_schedules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `result_id` int(11) NOT NULL,
  `interview_date` datetime NOT NULL,
  `interview_location` varchar(255) DEFAULT NULL,
  `interview_type` enum('online','physical') DEFAULT 'physical',
  `meeting_link` varchar(500) DEFAULT NULL,
  `interviewer_name` varchar(200) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `email_sent` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `result_id` (`result_id`),
  CONSTRAINT `interview_schedules_ibfk_1` FOREIGN KEY (`result_id`) REFERENCES `ai_screening_results` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `interview_schedules`
--

LOCK TABLES `interview_schedules` WRITE;
/*!40000 ALTER TABLE `interview_schedules` DISABLE KEYS */;
/*!40000 ALTER TABLE `interview_schedules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_applications`
--

DROP TABLE IF EXISTS `job_applications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `job_applications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `job_id` int(11) NOT NULL,
  `applicant_name` varchar(200) NOT NULL,
  `applicant_email` varchar(255) NOT NULL,
  `applicant_phone` varchar(20) DEFAULT NULL,
  `resume_path` varchar(500) DEFAULT NULL,
  `cover_letter` text DEFAULT NULL,
  `expected_salary` varchar(100) DEFAULT NULL,
  `status` enum('pending','reviewed','shortlisted','rejected','hired') DEFAULT 'pending',
  `applied_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_date` timestamp NULL DEFAULT NULL,
  `comments` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `reviewed_by` (`reviewed_by`),
  KEY `job_applications_ibfk_1` (`job_id`),
  CONSTRAINT `job_applications_ibfk_1` FOREIGN KEY (`job_id`) REFERENCES `job_postings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `job_applications_ibfk_2` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_applications`
--

LOCK TABLES `job_applications` WRITE;
/*!40000 ALTER TABLE `job_applications` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_applications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_postings`
--

DROP TABLE IF EXISTS `job_postings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `job_postings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `job_title` varchar(200) NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `job_type` enum('full_time','part_time','contract','internship') DEFAULT 'full_time',
  `experience_required` varchar(100) DEFAULT NULL,
  `education_required` text DEFAULT NULL,
  `skills_required` text DEFAULT NULL,
  `job_description` text DEFAULT NULL,
  `requirements` text DEFAULT NULL,
  `responsibilities` text DEFAULT NULL,
  `salary_range` varchar(100) DEFAULT NULL,
  `location` varchar(200) DEFAULT NULL,
  `vacancies` int(11) DEFAULT 1,
  `posted_by` int(11) DEFAULT NULL,
  `posted_date` date DEFAULT NULL,
  `last_date` date DEFAULT NULL,
  `status` enum('open','closed','on_hold') DEFAULT 'open',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_postings`
--

LOCK TABLES `job_postings` WRITE;
/*!40000 ALTER TABLE `job_postings` DISABLE KEYS */;
INSERT INTO `job_postings` VALUES (1,'Test Job','IT','full_time',NULL,NULL,NULL,'Test Description','Test Requirements',NULL,'50000','Dhaka',2,NULL,'2026-06-24','2026-07-30','open','2026-06-24 06:58:50','2026-06-24 06:58:50'),(2,'soft','HR','part_time',NULL,NULL,NULL,'sda','dfasf',NULL,'5000','dhaka',1,NULL,'2026-06-24','2026-06-26','open','2026-06-24 06:59:45','2026-06-24 06:59:45');
/*!40000 ALTER TABLE `job_postings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `leave_requests`
--

DROP TABLE IF EXISTS `leave_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `leave_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `leave_type` enum('casual','sick','earned','maternity') DEFAULT 'casual',
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `total_days` int(11) NOT NULL,
  `reason` text DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `applied_on` timestamp NOT NULL DEFAULT current_timestamp(),
  `approved_by` int(11) DEFAULT NULL,
  `approved_on` timestamp NULL DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `employee_id` (`employee_id`),
  KEY `approved_by` (`approved_by`),
  CONSTRAINT `leave_requests_ibfk_2` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `leave_requests`
--

LOCK TABLES `leave_requests` WRITE;
/*!40000 ALTER TABLE `leave_requests` DISABLE KEYS */;
INSERT INTO `leave_requests` VALUES (1,5,'sick','2026-06-14','2026-06-16',2,'sick','approved','2026-06-13 16:42:32',26,'2026-06-24 09:37:39',NULL),(3,15,'sick','2026-06-15','2026-06-20',6,'for hajj','approved','2026-06-15 14:30:26',7,'2026-06-22 04:14:14',NULL),(6,6,'casual','2026-06-29','2026-06-29',1,'very sick','rejected','2026-06-29 01:42:17',7,'2026-06-29 01:43:53','na'),(7,6,'sick','2026-06-29','2026-06-29',1,'dfs','approved','2026-06-29 15:19:33',7,'2026-06-29 15:20:18',NULL),(8,6,'earned','2026-06-30','2026-07-04',5,'dsd','rejected','2026-06-29 18:03:25',7,'2026-06-29 18:04:05','no reason'),(9,6,'maternity','2026-06-30','2026-06-30',1,'w','rejected','2026-06-29 18:34:11',7,'2026-06-30 02:46:02','no reason'),(10,6,'casual','2026-07-05','2026-07-07',3,'i am sick','pending','2026-07-05 08:42:32',NULL,NULL,NULL);
/*!40000 ALTER TABLE `leave_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `leaves`
--

DROP TABLE IF EXISTS `leaves`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `leaves` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `leave_type` enum('casual','sick','earned','maternity','paternity') NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `reason` text DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `applied_on` timestamp NOT NULL DEFAULT current_timestamp(),
  `approved_by` int(11) DEFAULT NULL,
  `approved_on` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `employee_id` (`employee_id`),
  KEY `approved_by` (`approved_by`),
  CONSTRAINT `leaves_ibfk_2` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `leaves`
--

LOCK TABLES `leaves` WRITE;
/*!40000 ALTER TABLE `leaves` DISABLE KEYS */;
/*!40000 ALTER TABLE `leaves` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notice_reads`
--

DROP TABLE IF EXISTS `notice_reads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notice_reads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `notice_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `read_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_read` (`notice_id`,`user_id`),
  KEY `idx_notice_id` (`notice_id`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notice_reads`
--

LOCK TABLES `notice_reads` WRITE;
/*!40000 ALTER TABLE `notice_reads` DISABLE KEYS */;
INSERT INTO `notice_reads` VALUES (2,3,7,'2026-06-29 18:34:51'),(4,1,7,'2026-06-29 18:34:53'),(5,4,7,'2026-06-30 02:27:05'),(6,5,7,'2026-07-08 14:25:36'),(7,6,7,'2026-07-08 14:25:38');
/*!40000 ALTER TABLE `notice_reads` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notices`
--

DROP TABLE IF EXISTS `notices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `notices_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notices`
--

LOCK TABLES `notices` WRITE;
/*!40000 ALTER TABLE `notices` DISABLE KEYS */;
INSERT INTO `notices` VALUES (1,'for meeting','tonight meetinf at 11:30 plz all employee must attend ',7,'2026-06-29 16:33:32','2026-06-29 16:33:32'),(3,'best employee declear','Shamim Reja is best employee in this year..!',7,'2026-06-29 18:15:24','2026-06-29 18:15:24'),(5,'notice for xam','today your xm',7,'2026-06-30 09:42:59','2026-06-30 09:42:59'),(6,'Notice for meeting','tommorrow 12:45 min you have meeting must be attend in this meeting',7,'2026-07-03 17:50:37','2026-07-03 17:50:37'),(7,'A','S',7,'2026-07-09 13:35:44','2026-07-09 13:35:44');
/*!40000 ALTER TABLE `notices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `type` enum('info','success','warning','danger') DEFAULT 'info',
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `idx_is_read` (`is_read`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=224 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES (1,1,'New Leave Request','shamim reja has applied for 2 days leave','info',0,'2026-06-24 09:53:16'),(2,4,'New Leave Request','shamim reja has applied for 2 days leave','info',0,'2026-06-24 09:53:16'),(4,8,'New Leave Request','shamim reja has applied for 2 days leave','info',0,'2026-06-24 09:53:16'),(5,16,'New Leave Request','shamim reja has applied for 2 days leave','info',0,'2026-06-24 09:53:16'),(6,21,'New Leave Request','shamim reja has applied for 2 days leave','info',0,'2026-06-24 09:53:16'),(7,22,'New Leave Request','shamim reja has applied for 2 days leave','info',0,'2026-06-24 09:53:16'),(8,25,'New Leave Request','shamim reja has applied for 2 days leave','info',0,'2026-06-24 09:53:16'),(9,26,'New Leave Request','shamim reja has applied for 2 days leave','info',0,'2026-06-24 09:53:16'),(11,1,'New Leave Request','shamim reja has applied for 1 days leave','info',0,'2026-06-29 01:42:17'),(12,4,'New Leave Request','shamim reja has applied for 1 days leave','info',0,'2026-06-29 01:42:17'),(14,8,'New Leave Request','shamim reja has applied for 1 days leave','info',0,'2026-06-29 01:42:17'),(15,16,'New Leave Request','shamim reja has applied for 1 days leave','info',0,'2026-06-29 01:42:17'),(16,21,'New Leave Request','shamim reja has applied for 1 days leave','info',0,'2026-06-29 01:42:17'),(17,22,'New Leave Request','shamim reja has applied for 1 days leave','info',0,'2026-06-29 01:42:17'),(18,25,'New Leave Request','shamim reja has applied for 1 days leave','info',0,'2026-06-29 01:42:17'),(19,26,'New Leave Request','shamim reja has applied for 1 days leave','info',0,'2026-06-29 01:42:17'),(26,6,'Leave Rejected','Your 1 days leave request has been rejected. Reason: na','danger',1,'2026-06-29 01:43:53'),(27,1,'New Leave Request','shamim reja has applied for 1 days leave','info',0,'2026-06-29 15:19:33'),(28,4,'New Leave Request','shamim reja has applied for 1 days leave','info',0,'2026-06-29 15:19:33'),(29,7,'New Leave Request','shamim reja has applied for 1 days leave','info',1,'2026-06-29 15:19:33'),(30,8,'New Leave Request','shamim reja has applied for 1 days leave','info',0,'2026-06-29 15:19:33'),(31,16,'New Leave Request','shamim reja has applied for 1 days leave','info',0,'2026-06-29 15:19:33'),(32,21,'New Leave Request','shamim reja has applied for 1 days leave','info',0,'2026-06-29 15:19:33'),(33,22,'New Leave Request','shamim reja has applied for 1 days leave','info',0,'2026-06-29 15:19:33'),(34,25,'New Leave Request','shamim reja has applied for 1 days leave','info',0,'2026-06-29 15:19:33'),(35,26,'New Leave Request','shamim reja has applied for 1 days leave','info',0,'2026-06-29 15:19:33'),(42,6,'Leave Approved','Your 1 days leave request has been approved','success',1,'2026-06-29 15:20:18'),(43,2,'📢 New Notice: for meeting','tonight meeting at 11:30 plz all employee must attend','info',0,'2026-06-29 16:46:42'),(44,5,'📢 New Notice: for meeting','tonight meeting at 11:30 plz all employee must attend','info',0,'2026-06-29 16:46:42'),(45,6,'📢 New Notice: for meeting','tonight meeting at 11:30 plz all employee must attend','info',1,'2026-06-29 16:46:42'),(46,13,'📢 New Notice: for meeting','tonight meeting at 11:30 plz all employee must attend','info',0,'2026-06-29 16:46:42'),(47,14,'📢 New Notice: for meeting','tonight meeting at 11:30 plz all employee must attend','info',0,'2026-06-29 16:46:42'),(48,15,'📢 New Notice: for meeting','tonight meeting at 11:30 plz all employee must attend','info',0,'2026-06-29 16:46:42'),(49,17,'📢 New Notice: for meeting','tonight meeting at 11:30 plz all employee must attend','info',0,'2026-06-29 16:46:42'),(50,18,'📢 New Notice: for meeting','tonight meeting at 11:30 plz all employee must attend','info',0,'2026-06-29 16:46:42'),(51,23,'📢 New Notice: for meeting','tonight meeting at 11:30 plz all employee must attend','info',0,'2026-06-29 16:46:42'),(52,24,'📢 New Notice: for meeting','tonight meeting at 11:30 plz all employee must attend','info',0,'2026-06-29 16:46:42'),(58,2,'📢 New Notice: Importent Notice','All employee are must attened tommorow.Cause Chairman will come in our office','info',0,'2026-06-29 17:04:28'),(59,5,'📢 New Notice: Importent Notice','All employee are must attened tommorow.Cause Chairman will come in our office','info',0,'2026-06-29 17:04:28'),(60,6,'📢 New Notice: Importent Notice','All employee are must attened tommorow.Cause Chairman will come in our office','info',1,'2026-06-29 17:04:28'),(61,13,'📢 New Notice: Importent Notice','All employee are must attened tommorow.Cause Chairman will come in our office','info',0,'2026-06-29 17:04:28'),(62,14,'📢 New Notice: Importent Notice','All employee are must attened tommorow.Cause Chairman will come in our office','info',0,'2026-06-29 17:04:28'),(63,15,'📢 New Notice: Importent Notice','All employee are must attened tommorow.Cause Chairman will come in our office','info',0,'2026-06-29 17:04:28'),(64,17,'📢 New Notice: Importent Notice','All employee are must attened tommorow.Cause Chairman will come in our office','info',0,'2026-06-29 17:04:28'),(65,18,'📢 New Notice: Importent Notice','All employee are must attened tommorow.Cause Chairman will come in our office','info',0,'2026-06-29 17:04:28'),(66,23,'📢 New Notice: Importent Notice','All employee are must attened tommorow.Cause Chairman will come in our office','info',0,'2026-06-29 17:04:28'),(67,24,'📢 New Notice: Importent Notice','All employee are must attened tommorow.Cause Chairman will come in our office','info',0,'2026-06-29 17:04:28'),(68,1,'📄 New Work Submission','shamim reja submitted: sf','info',0,'2026-06-29 17:27:13'),(69,4,'📄 New Work Submission','shamim reja submitted: sf','info',0,'2026-06-29 17:27:13'),(70,7,'📄 New Work Submission','shamim reja submitted: sf','info',1,'2026-06-29 17:27:13'),(71,8,'📄 New Work Submission','shamim reja submitted: sf','info',0,'2026-06-29 17:27:13'),(72,16,'📄 New Work Submission','shamim reja submitted: sf','info',0,'2026-06-29 17:27:13'),(73,21,'📄 New Work Submission','shamim reja submitted: sf','info',0,'2026-06-29 17:27:13'),(74,22,'📄 New Work Submission','shamim reja submitted: sf','info',0,'2026-06-29 17:27:13'),(75,25,'📄 New Work Submission','shamim reja submitted: sf','info',0,'2026-06-29 17:27:13'),(76,26,'📄 New Work Submission','shamim reja submitted: sf','info',0,'2026-06-29 17:27:13'),(77,6,'📝 Submission Update: sf','Your submission has been Reviewed. Feedback: not good.please wrrite again and submit me at 12pm','info',1,'2026-06-29 17:36:16'),(78,7,'📋 Submission Reviewed','You reviewed shamim\'s submission: sf','success',1,'2026-06-29 17:36:16'),(79,6,'⭐ New Performance Review','You have received a performance review from HR. Title: best employee in this year, Rating: 5/5 ⭐⭐⭐⭐⭐','success',1,'2026-06-29 17:55:10'),(80,1,'New Leave Request','shamim reja has applied for 5 days leave','info',0,'2026-06-29 18:03:25'),(81,4,'New Leave Request','shamim reja has applied for 5 days leave','info',0,'2026-06-29 18:03:25'),(82,7,'New Leave Request','shamim reja has applied for 5 days leave','info',1,'2026-06-29 18:03:25'),(83,8,'New Leave Request','shamim reja has applied for 5 days leave','info',0,'2026-06-29 18:03:25'),(84,16,'New Leave Request','shamim reja has applied for 5 days leave','info',0,'2026-06-29 18:03:25'),(85,21,'New Leave Request','shamim reja has applied for 5 days leave','info',0,'2026-06-29 18:03:25'),(86,22,'New Leave Request','shamim reja has applied for 5 days leave','info',0,'2026-06-29 18:03:25'),(87,25,'New Leave Request','shamim reja has applied for 5 days leave','info',0,'2026-06-29 18:03:25'),(88,26,'New Leave Request','shamim reja has applied for 5 days leave','info',0,'2026-06-29 18:03:25'),(95,6,'Leave Rejected','Your 5 days leave request has been rejected. Reason: no reason','danger',1,'2026-06-29 18:04:05'),(96,1,'📄 New Work Submission','shamim reja submitted: for check','info',0,'2026-06-29 18:13:22'),(97,4,'📄 New Work Submission','shamim reja submitted: for check','info',0,'2026-06-29 18:13:22'),(98,7,'📄 New Work Submission','shamim reja submitted: for check','info',1,'2026-06-29 18:13:22'),(99,8,'📄 New Work Submission','shamim reja submitted: for check','info',0,'2026-06-29 18:13:22'),(100,16,'📄 New Work Submission','shamim reja submitted: for check','info',0,'2026-06-29 18:13:22'),(101,21,'📄 New Work Submission','shamim reja submitted: for check','info',0,'2026-06-29 18:13:22'),(102,22,'📄 New Work Submission','shamim reja submitted: for check','info',0,'2026-06-29 18:13:22'),(103,25,'📄 New Work Submission','shamim reja submitted: for check','info',0,'2026-06-29 18:13:22'),(104,26,'📄 New Work Submission','shamim reja submitted: for check','info',0,'2026-06-29 18:13:22'),(105,6,'✅ Submission Update: for check','Your submission has been Approved. Feedback: Good job!!','info',1,'2026-06-29 18:14:20'),(106,7,'📋 Submission Reviewed','You reviewed shamim\'s submission: for check','success',1,'2026-06-29 18:14:20'),(107,2,'📢 New Notice: best employee declear','Shamim Reja is best employee in this year..!','info',0,'2026-06-29 18:15:24'),(108,5,'📢 New Notice: best employee declear','Shamim Reja is best employee in this year..!','info',0,'2026-06-29 18:15:24'),(109,6,'📢 New Notice: best employee declear','Shamim Reja is best employee in this year..!','info',1,'2026-06-29 18:15:24'),(110,13,'📢 New Notice: best employee declear','Shamim Reja is best employee in this year..!','info',0,'2026-06-29 18:15:24'),(111,14,'📢 New Notice: best employee declear','Shamim Reja is best employee in this year..!','info',0,'2026-06-29 18:15:24'),(112,15,'📢 New Notice: best employee declear','Shamim Reja is best employee in this year..!','info',0,'2026-06-29 18:15:24'),(113,17,'📢 New Notice: best employee declear','Shamim Reja is best employee in this year..!','info',0,'2026-06-29 18:15:24'),(114,18,'📢 New Notice: best employee declear','Shamim Reja is best employee in this year..!','info',0,'2026-06-29 18:15:24'),(115,23,'📢 New Notice: best employee declear','Shamim Reja is best employee in this year..!','info',0,'2026-06-29 18:15:24'),(116,24,'📢 New Notice: best employee declear','Shamim Reja is best employee in this year..!','info',0,'2026-06-29 18:15:24'),(117,1,'📄 New Work Submission','shamim reja submitted: asd','info',0,'2026-06-29 18:19:05'),(118,4,'📄 New Work Submission','shamim reja submitted: asd','info',0,'2026-06-29 18:19:05'),(119,7,'📄 New Work Submission','shamim reja submitted: asd','info',1,'2026-06-29 18:19:05'),(120,8,'📄 New Work Submission','shamim reja submitted: asd','info',0,'2026-06-29 18:19:05'),(121,16,'📄 New Work Submission','shamim reja submitted: asd','info',0,'2026-06-29 18:19:05'),(122,21,'📄 New Work Submission','shamim reja submitted: asd','info',0,'2026-06-29 18:19:05'),(123,22,'📄 New Work Submission','shamim reja submitted: asd','info',0,'2026-06-29 18:19:05'),(124,25,'📄 New Work Submission','shamim reja submitted: asd','info',0,'2026-06-29 18:19:05'),(125,26,'📄 New Work Submission','shamim reja submitted: asd','info',0,'2026-06-29 18:19:05'),(126,6,'✅ Submission Update: asd','Your submission has been Approved. Feedback: good job\n','info',1,'2026-06-29 18:20:06'),(127,7,'📋 Submission Reviewed','You reviewed shamim\'s submission: asd','success',1,'2026-06-29 18:20:06'),(128,2,'📢 New Notice: new notice','tgfh','info',0,'2026-06-29 18:21:36'),(129,5,'📢 New Notice: new notice','tgfh','info',0,'2026-06-29 18:21:36'),(130,6,'📢 New Notice: new notice','tgfh','info',1,'2026-06-29 18:21:36'),(131,13,'📢 New Notice: new notice','tgfh','info',0,'2026-06-29 18:21:36'),(132,14,'📢 New Notice: new notice','tgfh','info',0,'2026-06-29 18:21:36'),(133,15,'📢 New Notice: new notice','tgfh','info',0,'2026-06-29 18:21:36'),(134,17,'📢 New Notice: new notice','tgfh','info',0,'2026-06-29 18:21:36'),(135,18,'📢 New Notice: new notice','tgfh','info',0,'2026-06-29 18:21:36'),(136,23,'📢 New Notice: new notice','tgfh','info',0,'2026-06-29 18:21:36'),(137,24,'📢 New Notice: new notice','tgfh','info',0,'2026-06-29 18:21:36'),(138,1,'New Leave Request','shamim reja has applied for 1 days leave','info',0,'2026-06-29 18:34:11'),(139,4,'New Leave Request','shamim reja has applied for 1 days leave','info',0,'2026-06-29 18:34:11'),(140,7,'New Leave Request','shamim reja has applied for 1 days leave','info',1,'2026-06-29 18:34:11'),(141,8,'New Leave Request','shamim reja has applied for 1 days leave','info',0,'2026-06-29 18:34:11'),(142,16,'New Leave Request','shamim reja has applied for 1 days leave','info',0,'2026-06-29 18:34:11'),(143,21,'New Leave Request','shamim reja has applied for 1 days leave','info',0,'2026-06-29 18:34:11'),(144,22,'New Leave Request','shamim reja has applied for 1 days leave','info',0,'2026-06-29 18:34:11'),(145,25,'New Leave Request','shamim reja has applied for 1 days leave','info',0,'2026-06-29 18:34:11'),(146,26,'New Leave Request','shamim reja has applied for 1 days leave','info',0,'2026-06-29 18:34:11'),(153,6,'⭐ New Performance Review','You have received a performance review from HR. Title: for backend, Rating: 3/5 ⭐⭐⭐','success',1,'2026-06-29 19:35:07'),(154,7,'✅ Performance Review Submitted','You have submitted a performance review for shamim reja (Title: for backend, Rating: 3/5)','success',1,'2026-06-29 19:35:07'),(155,1,'📄 New Work Submission','adu vai submitted: Review Cv','info',0,'2026-06-30 02:34:24'),(156,4,'📄 New Work Submission','adu vai submitted: Review Cv','info',0,'2026-06-30 02:34:24'),(157,7,'📄 New Work Submission','adu vai submitted: Review Cv','info',1,'2026-06-30 02:34:24'),(158,8,'📄 New Work Submission','adu vai submitted: Review Cv','info',0,'2026-06-30 02:34:24'),(159,16,'📄 New Work Submission','adu vai submitted: Review Cv','info',0,'2026-06-30 02:34:24'),(160,21,'📄 New Work Submission','adu vai submitted: Review Cv','info',0,'2026-06-30 02:34:24'),(161,22,'📄 New Work Submission','adu vai submitted: Review Cv','info',0,'2026-06-30 02:34:24'),(162,25,'📄 New Work Submission','adu vai submitted: Review Cv','info',0,'2026-06-30 02:34:24'),(163,26,'📄 New Work Submission','adu vai submitted: Review Cv','info',0,'2026-06-30 02:34:24'),(164,27,'⏳ Submission Update: Review Cv','Your submission has been Pending. Feedback: not good cv.that not enogh skill for our company ','info',1,'2026-06-30 02:35:52'),(165,7,'📋 Submission Reviewed','You reviewed adu\'s submission: Review Cv','success',1,'2026-06-30 02:35:52'),(166,6,'Leave Rejected','Your 1 days leave request has been rejected. Reason: no reason','danger',1,'2026-06-30 02:46:02'),(167,2,'📢 New Notice: notice for xam','today your xm','info',0,'2026-06-30 09:42:59'),(168,6,'📢 New Notice: notice for xam','today your xm','info',1,'2026-06-30 09:42:59'),(169,13,'📢 New Notice: notice for xam','today your xm','info',0,'2026-06-30 09:42:59'),(170,14,'📢 New Notice: notice for xam','today your xm','info',0,'2026-06-30 09:42:59'),(171,15,'📢 New Notice: notice for xam','today your xm','info',0,'2026-06-30 09:42:59'),(172,17,'📢 New Notice: notice for xam','today your xm','info',0,'2026-06-30 09:42:59'),(173,18,'📢 New Notice: notice for xam','today your xm','info',0,'2026-06-30 09:42:59'),(174,23,'📢 New Notice: notice for xam','today your xm','info',0,'2026-06-30 09:42:59'),(175,24,'📢 New Notice: notice for xam','today your xm','info',0,'2026-06-30 09:42:59'),(176,27,'📢 New Notice: notice for xam','today your xm','info',0,'2026-06-30 09:42:59'),(177,2,'📢 New Notice: Notice for meeting','tommorrow 12:45 min you have meeting must be attend in this meeting','info',0,'2026-07-03 17:50:37'),(178,6,'📢 New Notice: Notice for meeting','tommorrow 12:45 min you have meeting must be attend in this meeting','info',1,'2026-07-03 17:50:37'),(179,13,'📢 New Notice: Notice for meeting','tommorrow 12:45 min you have meeting must be attend in this meeting','info',0,'2026-07-03 17:50:37'),(180,14,'📢 New Notice: Notice for meeting','tommorrow 12:45 min you have meeting must be attend in this meeting','info',0,'2026-07-03 17:50:37'),(181,15,'📢 New Notice: Notice for meeting','tommorrow 12:45 min you have meeting must be attend in this meeting','info',0,'2026-07-03 17:50:37'),(182,17,'📢 New Notice: Notice for meeting','tommorrow 12:45 min you have meeting must be attend in this meeting','info',0,'2026-07-03 17:50:37'),(183,18,'📢 New Notice: Notice for meeting','tommorrow 12:45 min you have meeting must be attend in this meeting','info',0,'2026-07-03 17:50:37'),(184,23,'📢 New Notice: Notice for meeting','tommorrow 12:45 min you have meeting must be attend in this meeting','info',0,'2026-07-03 17:50:37'),(185,24,'📢 New Notice: Notice for meeting','tommorrow 12:45 min you have meeting must be attend in this meeting','info',0,'2026-07-03 17:50:37'),(186,27,'📢 New Notice: Notice for meeting','tommorrow 12:45 min you have meeting must be attend in this meeting','info',0,'2026-07-03 17:50:37'),(187,1,'📄 New Work Submission','shamim reja submitted: new task','info',0,'2026-07-04 07:42:06'),(188,4,'📄 New Work Submission','shamim reja submitted: new task','info',0,'2026-07-04 07:42:06'),(189,7,'📄 New Work Submission','shamim reja submitted: new task','info',1,'2026-07-04 07:42:06'),(190,8,'📄 New Work Submission','shamim reja submitted: new task','info',0,'2026-07-04 07:42:06'),(191,16,'📄 New Work Submission','shamim reja submitted: new task','info',0,'2026-07-04 07:42:06'),(192,21,'📄 New Work Submission','shamim reja submitted: new task','info',0,'2026-07-04 07:42:06'),(193,22,'📄 New Work Submission','shamim reja submitted: new task','info',0,'2026-07-04 07:42:06'),(194,25,'📄 New Work Submission','shamim reja submitted: new task','info',0,'2026-07-04 07:42:06'),(195,26,'📄 New Work Submission','shamim reja submitted: new task','info',0,'2026-07-04 07:42:06'),(196,6,'📝 Submission Update: new task','Your submission has been Reviewed. Feedback: good job','info',1,'2026-07-04 07:43:10'),(197,7,'📋 Submission Reviewed','You reviewed shamim\'s submission: new task','success',1,'2026-07-04 07:43:10'),(198,1,'New Leave Request','shamim reja has applied for 3 days leave','info',0,'2026-07-05 08:42:32'),(199,4,'New Leave Request','shamim reja has applied for 3 days leave','info',0,'2026-07-05 08:42:32'),(200,7,'New Leave Request','shamim reja has applied for 3 days leave','info',1,'2026-07-05 08:42:32'),(201,8,'New Leave Request','shamim reja has applied for 3 days leave','info',0,'2026-07-05 08:42:32'),(202,16,'New Leave Request','shamim reja has applied for 3 days leave','info',0,'2026-07-05 08:42:32'),(203,21,'New Leave Request','shamim reja has applied for 3 days leave','info',0,'2026-07-05 08:42:32'),(204,22,'New Leave Request','shamim reja has applied for 3 days leave','info',0,'2026-07-05 08:42:32'),(205,25,'New Leave Request','shamim reja has applied for 3 days leave','info',0,'2026-07-05 08:42:32'),(206,26,'New Leave Request','shamim reja has applied for 3 days leave','info',0,'2026-07-05 08:42:32'),(213,6,'📝 Submission Update: new task','Your submission has been Reviewed. Feedback: good job','info',1,'2026-07-05 09:23:25'),(214,7,'📋 Submission Reviewed','You reviewed shamim\'s submission: new task','success',1,'2026-07-05 09:23:25'),(215,6,'📢 New Notice: A','S','info',1,'2026-07-09 13:35:44'),(216,13,'📢 New Notice: A','S','info',0,'2026-07-09 13:35:44'),(217,14,'📢 New Notice: A','S','info',0,'2026-07-09 13:35:44'),(218,15,'📢 New Notice: A','S','info',0,'2026-07-09 13:35:44'),(219,17,'📢 New Notice: A','S','info',0,'2026-07-09 13:35:44'),(220,18,'📢 New Notice: A','S','info',0,'2026-07-09 13:35:44'),(221,23,'📢 New Notice: A','S','info',0,'2026-07-09 13:35:44'),(222,24,'📢 New Notice: A','S','info',0,'2026-07-09 13:35:44'),(223,27,'📢 New Notice: A','S','info',0,'2026-07-09 13:35:44');
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payroll`
--

DROP TABLE IF EXISTS `payroll`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payroll` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `period_start` date DEFAULT NULL,
  `period_end` date DEFAULT NULL,
  `month_year` varchar(15) NOT NULL,
  `basic_salary` decimal(10,2) NOT NULL,
  `house_rent` decimal(10,2) DEFAULT 0.00,
  `medical_allowance` decimal(10,2) DEFAULT 0.00,
  `travel_allowance` decimal(10,2) DEFAULT 0.00,
  `other_allowance` decimal(10,2) DEFAULT 0.00,
  `total_allowance` decimal(10,2) DEFAULT 0.00,
  `provident_fund` decimal(10,2) DEFAULT 0.00,
  `tax` decimal(10,2) DEFAULT 0.00,
  `other_deductions` decimal(10,2) DEFAULT 0.00,
  `total_deductions` decimal(10,2) DEFAULT 0.00,
  `net_salary` decimal(10,2) NOT NULL,
  `payment_date` date DEFAULT NULL,
  `status` enum('pending','processed','paid') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `employee_id` (`employee_id`),
  KEY `idx_month_year` (`month_year`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=46 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payroll`
--

LOCK TABLES `payroll` WRITE;
/*!40000 ALTER TABLE `payroll` DISABLE KEYS */;
INSERT INTO `payroll` VALUES (1,2,NULL,NULL,'June 2026',50000.00,25000.00,5000.00,2500.00,0.00,32500.00,5000.00,2500.00,0.00,7500.00,75000.00,'2026-06-30','processed','2026-06-15 07:33:37','2026-06-15 07:33:37'),(2,5,NULL,NULL,'June 2026',0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,'2026-06-30','processed','2026-06-15 07:33:37','2026-06-15 07:33:37'),(3,6,NULL,NULL,'June 2026',0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,'2026-06-30','processed','2026-06-15 07:33:37','2026-06-15 07:33:37'),(5,2,NULL,NULL,'June 2026',50000.00,25000.00,5000.00,2500.00,0.00,32500.00,5000.00,2500.00,0.00,7500.00,75000.00,'2026-06-30','processed','2026-06-15 07:35:37','2026-06-15 07:35:37'),(6,5,NULL,NULL,'June 2026',0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,'2026-06-30','processed','2026-06-15 07:35:37','2026-06-15 07:35:37'),(7,6,NULL,NULL,'June 2026',0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,'2026-06-30','processed','2026-06-15 07:35:37','2026-06-15 07:35:37'),(9,2,NULL,NULL,'June 2026',50000.00,25000.00,5000.00,2500.00,0.00,32500.00,5000.00,2500.00,0.00,7500.00,75000.00,'2026-06-30','processed','2026-06-15 07:40:52','2026-06-15 07:40:52'),(10,5,NULL,NULL,'June 2026',0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,'2026-06-30','processed','2026-06-15 07:40:52','2026-06-15 07:40:52'),(11,6,NULL,NULL,'June 2026',0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,'2026-06-30','processed','2026-06-15 07:40:52','2026-06-15 07:40:52'),(13,2,NULL,NULL,'June 2026',50000.00,25000.00,5000.00,2500.00,0.00,32500.00,5000.00,2500.00,0.00,7500.00,75000.00,'2026-06-30','processed','2026-06-15 07:41:40','2026-06-15 07:41:40'),(14,5,NULL,NULL,'June 2026',0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,'2026-06-30','processed','2026-06-15 07:41:40','2026-06-15 07:41:40'),(15,6,NULL,NULL,'June 2026',0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,'2026-06-30','processed','2026-06-15 07:41:40','2026-06-15 07:41:40'),(17,2,NULL,NULL,'June 2026',50000.00,25000.00,5000.00,2500.00,0.00,32500.00,5000.00,2500.00,0.00,7500.00,75000.00,'2026-06-30','processed','2026-06-15 07:46:42','2026-06-15 07:46:42'),(18,5,NULL,NULL,'June 2026',0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,'2026-06-30','processed','2026-06-15 07:46:42','2026-06-15 07:46:42'),(19,6,NULL,NULL,'June 2026',0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,'2026-06-30','processed','2026-06-15 07:46:42','2026-06-15 07:46:42'),(21,2,NULL,NULL,'June 2026',5000.00,2500.00,500.00,250.00,0.00,3250.00,500.00,250.00,0.00,750.00,7500.00,'2026-06-22','processed','2026-06-22 04:51:27','2026-06-22 04:51:27'),(22,5,NULL,NULL,'June 2026',0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,'2026-06-22','processed','2026-06-22 04:51:27','2026-06-22 04:51:27'),(23,6,NULL,NULL,'June 2026',0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,'2026-06-22','processed','2026-06-22 04:51:27','2026-06-22 04:51:27'),(24,13,NULL,NULL,'June 2026',5.00,2.50,0.50,0.25,0.00,3.25,0.50,0.25,0.00,0.75,7.50,'2026-06-22','processed','2026-06-22 04:51:27','2026-06-22 04:51:27'),(25,14,NULL,NULL,'June 2026',1500.00,750.00,150.00,75.00,0.00,975.00,150.00,75.00,0.00,225.00,2250.00,'2026-06-22','processed','2026-06-22 04:51:27','2026-06-22 04:51:27'),(26,15,NULL,NULL,'June 2026',0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,'2026-06-22','processed','2026-06-22 04:51:27','2026-06-22 04:51:27'),(27,17,NULL,NULL,'June 2026',15000.00,7500.00,1500.00,750.00,0.00,9750.00,1500.00,750.00,0.00,2250.00,22500.00,'2026-06-22','processed','2026-06-22 04:51:27','2026-06-22 04:51:27'),(28,2,NULL,NULL,'June 2026',5000.00,2500.00,500.00,250.00,0.00,3250.00,500.00,250.00,0.00,750.00,7500.00,'2026-06-24','processed','2026-06-24 08:26:44','2026-06-24 08:26:44'),(29,5,NULL,NULL,'June 2026',0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,'2026-06-24','processed','2026-06-24 08:26:44','2026-06-24 08:26:44'),(30,6,NULL,NULL,'June 2026',0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,'2026-06-24','processed','2026-06-24 08:26:44','2026-06-24 08:26:44'),(31,13,NULL,NULL,'June 2026',5.00,2.50,0.50,0.25,0.00,3.25,0.50,0.25,0.00,0.75,7.50,'2026-06-24','processed','2026-06-24 08:26:44','2026-06-24 08:26:44'),(32,14,NULL,NULL,'June 2026',1500.00,750.00,150.00,75.00,0.00,975.00,150.00,75.00,0.00,225.00,2250.00,'2026-06-24','processed','2026-06-24 08:26:44','2026-06-24 08:26:44'),(33,15,NULL,NULL,'June 2026',0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,'2026-06-24','processed','2026-06-24 08:26:44','2026-06-24 08:26:44'),(34,17,NULL,NULL,'June 2026',15000.00,7500.00,1500.00,750.00,0.00,9750.00,1500.00,750.00,0.00,2250.00,22500.00,'2026-06-24','processed','2026-06-24 08:26:44','2026-06-24 08:26:44'),(35,18,NULL,NULL,'June 2026',20000.00,10000.00,2000.00,1000.00,0.00,13000.00,2000.00,1000.00,0.00,3000.00,30000.00,'2026-06-24','processed','2026-06-24 08:26:44','2026-06-24 08:26:44'),(36,2,'2026-06-30','2026-06-30','June 2026',5000.00,2500.00,500.00,250.00,0.00,3250.00,500.00,250.00,0.00,750.00,7500.00,'2026-06-30','processed','2026-06-30 02:40:50','2026-06-30 02:40:50'),(37,6,'2026-06-30','2026-06-30','June 2026',0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,'2026-06-30','processed','2026-06-30 02:40:50','2026-06-30 02:40:50'),(38,13,'2026-06-30','2026-06-30','June 2026',5.00,2.50,0.50,0.25,0.00,3.25,0.50,0.25,0.00,0.75,7.50,'2026-06-30','processed','2026-06-30 02:40:50','2026-06-30 02:40:50'),(39,14,'2026-06-30','2026-06-30','June 2026',1500.00,750.00,150.00,75.00,0.00,975.00,150.00,75.00,0.00,225.00,2250.00,'2026-06-30','processed','2026-06-30 02:40:50','2026-06-30 02:40:50'),(40,15,'2026-06-30','2026-06-30','June 2026',0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,'2026-06-30','processed','2026-06-30 02:40:50','2026-06-30 02:40:50'),(41,17,'2026-06-30','2026-06-30','June 2026',15000.00,7500.00,1500.00,750.00,0.00,9750.00,1500.00,750.00,0.00,2250.00,22500.00,'2026-06-30','processed','2026-06-30 02:40:50','2026-06-30 02:40:50'),(42,18,'2026-06-30','2026-06-30','June 2026',20000.00,10000.00,2000.00,1000.00,0.00,13000.00,2000.00,1000.00,0.00,3000.00,30000.00,'2026-06-30','processed','2026-06-30 02:40:50','2026-06-30 02:40:50'),(43,23,'2026-06-30','2026-06-30','June 2026',15000.00,7500.00,1500.00,750.00,0.00,9750.00,1500.00,750.00,0.00,2250.00,22500.00,'2026-06-30','processed','2026-06-30 02:40:50','2026-06-30 02:40:50'),(44,24,'2026-06-30','2026-06-30','June 2026',200000.00,100000.00,20000.00,10000.00,0.00,130000.00,20000.00,10000.00,0.00,30000.00,300000.00,'2026-06-30','processed','2026-06-30 02:40:50','2026-06-30 02:40:50'),(45,27,'2026-06-30','2026-06-30','June 2026',120000.00,60000.00,12000.00,6000.00,0.00,78000.00,12000.00,6000.00,0.00,18000.00,180000.00,'2026-06-30','processed','2026-06-30 02:40:50','2026-06-30 02:40:50');
/*!40000 ALTER TABLE `payroll` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `performance_reviews`
--

DROP TABLE IF EXISTS `performance_reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `performance_reviews` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `reviewer_id` int(11) NOT NULL,
  `review_date` date NOT NULL,
  `review_period` varchar(50) DEFAULT NULL,
  `technical_skills` int(11) DEFAULT NULL CHECK (`technical_skills` between 1 and 5),
  `communication` int(11) DEFAULT NULL CHECK (`communication` between 1 and 5),
  `teamwork` int(11) DEFAULT NULL CHECK (`teamwork` between 1 and 5),
  `leadership` int(11) DEFAULT NULL CHECK (`leadership` between 1 and 5),
  `problem_solving` int(11) DEFAULT NULL CHECK (`problem_solving` between 1 and 5),
  `overall_rating` int(11) DEFAULT NULL CHECK (`overall_rating` between 1 and 5),
  `strengths` text DEFAULT NULL,
  `weaknesses` text DEFAULT NULL,
  `goals` text DEFAULT NULL,
  `comments` text DEFAULT NULL,
  `status` enum('scheduled','completed','cancelled') DEFAULT 'scheduled',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `review_title` varchar(200) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `employee_id` (`employee_id`),
  KEY `reviewer_id` (`reviewer_id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `performance_reviews`
--

LOCK TABLES `performance_reviews` WRITE;
/*!40000 ALTER TABLE `performance_reviews` DISABLE KEYS */;
INSERT INTO `performance_reviews` VALUES (1,5,7,'2026-06-22',NULL,NULL,NULL,NULL,NULL,NULL,2,NULL,NULL,NULL,'GOOD','scheduled','2026-06-22 05:42:32','MODERATE'),(2,15,7,'2026-06-22',NULL,NULL,NULL,NULL,NULL,NULL,5,NULL,NULL,NULL,'nice work','scheduled','2026-06-22 05:47:34','THE SUPER HERO'),(3,15,7,'2026-06-22',NULL,NULL,NULL,NULL,NULL,NULL,5,NULL,NULL,NULL,'','scheduled','2026-06-22 05:54:00',''),(5,5,7,'2026-06-23',NULL,NULL,NULL,NULL,NULL,NULL,2,NULL,NULL,NULL,'vb','scheduled','2026-06-23 10:13:33','THE SUPER HERO'),(6,14,26,'2026-06-24',NULL,NULL,NULL,NULL,NULL,NULL,2,NULL,NULL,NULL,'good job','scheduled','2026-06-24 09:40:04','for backend'),(7,17,7,'2026-06-24',NULL,NULL,NULL,NULL,NULL,NULL,3,NULL,NULL,NULL,'carry on','scheduled','2026-06-24 16:29:36','good work'),(8,6,7,'2026-06-28',NULL,NULL,NULL,NULL,NULL,NULL,5,NULL,NULL,NULL,'wd','scheduled','2026-06-27 18:40:09','w'),(9,6,7,'2026-06-29',NULL,NULL,NULL,NULL,NULL,NULL,5,NULL,NULL,NULL,'Congresulation. You are the select as best employee in this year.','completed','2026-06-29 17:41:37','best employee in this year'),(10,6,7,'2026-06-30','Monthly',3,3,3,3,3,3,'ds','sds','','sdf','completed','2026-06-29 19:35:07','for backend');
/*!40000 ALTER TABLE `performance_reviews` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` varchar(50) DEFAULT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `user_type` enum('hr','employee') DEFAULT 'employee',
  `department` varchar(100) DEFAULT NULL,
  `position` varchar(100) DEFAULT NULL,
  `salary` decimal(10,2) DEFAULT 0.00,
  `status` enum('active','inactive','probation') DEFAULT 'active',
  `join_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `profile_picture` varchar(500) DEFAULT NULL,
  `leave_balance` int(11) DEFAULT 20,
  `blood_group` varchar(10) DEFAULT NULL,
  `emergency_contact` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `employee_id` (`employee_id`),
  KEY `idx_email` (`email`),
  KEY `idx_employee_id` (`employee_id`)
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'HR001','Admin','KormoShathi','admin@kormoshathi.com',NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','hr',NULL,NULL,0.00,'active','2026-06-13','2026-06-13 11:18:06','2026-06-13 11:18:06',NULL,20,NULL,NULL,NULL,NULL),(4,'HR20266892','rahim','molla','abc@gmail.com','01727243306','$2y$10$GxG8rl71nQMWRObkOQV9QuHafrqpyNxpSyMwMFe69iDZTCr778bu2','hr','HR','junior soft',0.00,'active','2026-06-13','2026-06-13 14:55:25','2026-06-13 14:55:25',NULL,20,NULL,NULL,NULL,NULL),(6,'EMP20269107','shamim','reja','shamim123@gmail.com','019552826095','$2y$10$WpnIgrtTXdX1ihqUrIv8Zu0eVNexqZYaKQEVTH/mNgdgZdP/EVTxK','employee','IT','junior soft',0.00,'active','2026-06-13','2026-06-13 17:32:10','2026-06-29 15:20:18','uploads/user_6_1782211085.png',15,NULL,NULL,NULL,NULL),(7,'HR20268955','Abdul','Alim','abdulalim528260@gmail.com','01704281617','$2y$10$h10jZVsjI/.WD8OFbFHJ4eL4gJl3bIKdEm.gjUSdEdQtWWkDS88Ii','hr','HR','Developer',0.00,'active','2026-06-13','2026-06-13 17:37:52','2026-06-26 02:56:26','uploads/user_7_1782442586.jpeg',20,NULL,NULL,NULL,NULL),(8,'HR20268487','Abdul','Alim','abdulalim52826095@gmail.com','01704281617','$2y$10$TE0m6e/D3UtkICRv.xzr6uH.kU4IbEStuxofTBOCYK/UP4jpRaefK','hr','HR','junior soft',0.00,'active','2026-06-15','2026-06-15 05:59:25','2026-06-15 05:59:25',NULL,20,NULL,NULL,NULL,NULL),(13,'EMP20267230','summa','khatun','summa123@kormoshathigmail.com','0198923123','$2y$10$bwcDAuH/5JnzXzgfWUVareZ4b.65Qyj.wWP9rf6.hyXkABK5CRlxi','employee','Marketing','salse',5.00,'active','2026-06-14','2026-06-15 09:30:11','2026-06-15 10:11:00',NULL,20,NULL,NULL,NULL,NULL),(14,'EMP20261481','as','df','aadff1232@GMAIL.COM','01922863522','$2y$10$0GVKfyVDh2Ehl16rBJeJPekluOezhNtc.Uf0F1VP/rbcvZdoHkHQC','employee','Marketing','Developer',1500.00,'active','2026-06-15','2026-06-15 10:13:18','2026-06-15 10:25:34',NULL,20,NULL,NULL,NULL,NULL),(15,'EMP20268837','Azizul','Haque','azizulhaque123@gmail.com','01717969451','$2y$10$FtuQ.M2gOl.Jb/D69QO6jOTKtWUK9BoDFrQ9K8iPHfg3zUvhTtIni','employee','HR','Developer',0.00,'active','2026-06-15','2026-06-15 14:29:40','2026-06-22 04:14:14',NULL,14,NULL,NULL,NULL,NULL),(16,'HR20265994','Alim','biswas','alimbiswas@gmail.com','+8801957123090','$2y$10$COjePkQECbDNgdWloETyA.ucwqpW2nZ/3L.05Y8uDpaQOtIu2gKsW','hr','HR','Developer',0.00,'active','2026-06-22','2026-06-22 03:10:28','2026-06-22 03:10:28',NULL,20,NULL,NULL,NULL,NULL),(17,'EMP20269770','sumon','alim','sumonalim123@gmail.com','01704281617','$2y$10$3zHewRXdUMHbG9.2r9M5HOpwMWGAh7g.q8zsahVjzPjquKLgWEEjq','employee','Marketing','Developer',15000.00,'active','2026-06-22','2026-06-22 03:19:32','2026-06-22 03:19:32',NULL,20,NULL,NULL,NULL,NULL),(18,'EMP20266655','md.nazmul','islam','nazmul.kormoshathi@gmail.com','01725826095','$2y$10$Z9RZxF460sxE6Tfhr2A44uq8q0078bTb9nJ3qCgqA18NDq7wElLvq','employee','Finance','senior officer',20000.00,'active','2026-06-24','2026-06-24 08:25:42','2026-06-24 08:25:42',NULL,20,NULL,NULL,NULL,NULL),(21,'HR20267279','jannatul ','ferdouse','jannatul.kormosathe@gmail.com','019999043586','$2y$10$pWjegDPBNkY4St.r1Wq/DOSQ49JnZYp6SB8y5XWHE81Z2fTJd3k1i','hr','Operations','Developer',120000.00,'active','2026-06-24','2026-06-24 09:28:13','2026-06-24 09:28:13',NULL,20,NULL,NULL,NULL,NULL),(22,'HR20269988','synthia','ferdouse','shnthia.kormosathe@gmail.com','01957123090','$2y$10$lMJfLC.A7XE5zqkCI4M9bu28vY.KFlg0WdFvceHZGnTJMPQUmknr2','hr','HR','Developer',120000.00,'active','2026-06-24','2026-06-24 09:30:24','2026-06-24 09:30:24',NULL,20,NULL,NULL,NULL,NULL),(23,'EMP20263352','alim','vai','alim.kormoshathi123@gmail.com','01704281617','$2y$10$kubdmy3q2frT02yxRXuipO.nZSOQXko3iTbOBNPCxphESxLWMvQxm','employee','IT','Developer',15000.00,'active','2026-06-25','2026-06-24 09:32:33','2026-06-24 09:32:33',NULL,20,NULL,NULL,NULL,NULL),(24,'EMP20268919','JEBUN','NESA','JEBUN.KORMOSHATHI123@GMAIL.COM','01922869522','$2y$10$yPrP19NN/r4f3gmGMPtmJ.RqiH9TwJEus/fPh4cokkmLwZZwrw7.i','employee','Marketing','salse',200000.00,'active','2026-06-24','2026-06-24 09:34:10','2026-06-24 09:34:10',NULL,20,NULL,NULL,NULL,NULL),(25,'HR20262203','JEBUN','NESA','jebunnesa@gmail.com','01957123090','$2y$10$56qfD1tEOtibpHBLWNFtue4oG9X4fLcbyg4JBeOFzGsfqrgEuMyVO','hr','Sales','Developer',120000.00,'active','2026-06-24','2026-06-24 09:35:19','2026-06-24 09:35:19',NULL,20,NULL,NULL,NULL,NULL),(26,'HR20266939','synthia ','ferdouse','synthiaferdouse.kormoshathi@gmail.com','019999043586','$2y$10$WwHt1QcHVajz3I0qIhv8S.mO7IgL1mJp/wqI4Nxvsogipq/q0uJwe','hr','HR','senior officer',12.00,'active','2026-06-24','2026-06-24 09:36:53','2026-06-24 09:36:53',NULL,20,NULL,NULL,NULL,NULL),(27,'EMP20265049','adu','vai','aduvai.kormoshathi@gmail.com','01650242904','$2y$10$T2uyRuq.iknGrnrEWWM09eRvRsILMUp8m56BNKLP5hE4L65ecUBqm','employee','Sales','senior officer',120000.00,'active','2026-06-30','2026-06-30 02:31:05','2026-06-30 03:28:32','uploads/user_27_1782787044.jpg',20,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;


-- Dump completed on 2026-07-10 16:40:44
SET FOREIGN_KEY_CHECKS=1;
