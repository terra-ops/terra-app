# -*- mode: ruby -*-
# vi: set ft=ruby :

Vagrant.configure(2) do |config|
  config.vm.define 'terra' do |t|
    t.vm.box = "ubuntu/trusty64"
    t.vm.hostname = "terra"
    t.vm.network "private_network", ip: "7.3.22.4"

    # Run the terra install script
    t.vm.provision "shell",
      path: "install.sh"

    # Add "vagrant" user to the docker group.
    t.vm.provision "shell",
      inline: "usermod -aG docker vagrant"

    # Create an SSH public key for the vagrant user
    t.vm.provision "shell",
      inline: "su vagrant -c 'ssh-keygen -t rsa -N \"\" -f ~/.ssh/id_rsa' "
  end

  config.vm.provider "virtualbox" do |v|
    v.memory = 2048
    v.cpus = 2
  end

end
