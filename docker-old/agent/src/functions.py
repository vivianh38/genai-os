import os, logging, re, gzip, pickle, requests
from flask import make_response
from json import dumps
from src.variable import *

class ANSIEscapeCodeRemovalHandler(logging.Formatter):
    ANSI_ESCAPE = re.compile(r'\x1B\[[0-?]*[ -/]*[@-~]')

    def format(self, record):
        message = super().format(record)
        if re.search(r'\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3} - - \[\d{2}/[A-Za-z]{3}/\d{4} \d{2}:\d{2}:\d{2}\] "[^"]*" \d{3} -',message):
            message = re.sub(r'\[\d{1,2}/[A-Za-z]{3}/\d{4} \d{2}:\d{2}:\d{2}\]', "", message).replace(" - -  ", " -> ")[:-2]
        return f"[{datetime.now().strftime('%Y-%m-%d %H-%M-%S.%f')}]" + self.ANSI_ESCAPE.sub('', message)

def init_folder(path):
    if not os.path.exists(path):
        os.mkdir(path)

def log(state, *args, **kwargs):
    log_level = ["INFO", "WARNING", "CRITIAL"]
    message = ' '.join(map(str, args))
    msg = f"[{datetime.now().strftime('%Y-%m-%d %H-%M-%S.%f')}][{log_level[state]}] {message}"
    print(msg)
    with open(log_file_path, "a") as file:
        file.write(msg + "\n")

def save_variable_to_file(filename, data):
    with gzip.open(filename, 'wb') as file:
        pickle.dump(data, file, protocol=pickle.HIGHEST_PROTOCOL)
    log(0,"Records saved\n",data)

def load_variable_from_file(filename):
    with gzip.open(filename, 'rb') as file:
        return pickle.load(file)
        
def loadRecords(var, keep_state = False):
    log(0,"Loading records, Here's before\n",data)
    for i,o in var.items():
        data[i] = []
        for k in o:
            try:
                resp = requests.post(k[0])
                if resp.status_code == 200:
                    data[i].append(k if keep_state else [k[0],"READY",-1,-1])
            except requests.exceptions.ConnectionError as e:
                log(0,f"Healthy check failed in {i} at {k[0]}, removed")
        if len(data[i]) == 0: del data[i]
    log(0,"Records loaded, Here's After\n",data)